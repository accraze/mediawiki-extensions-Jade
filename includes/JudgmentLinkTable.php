<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace Jade;

use Status;
use StatusValue;
use Wikimedia\Rdbms\LoadBalancer;
use WikiPage;

/**
 * Judgment index storage implemented using a RDBMS link table and indexes.
 */
class JudgmentLinkTable implements JudgmentIndexStorage {

	private $loadBalancer;

	public function __construct( LoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	public function insertIndex( JudgmentTarget $target, WikiPage $judgmentPage ) {
		$tableHelper = new JudgmentLinkTableHelper( $target->entityType );

		// Create row linking the judgment and its target.
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );
		$row = [
			$tableHelper->getTargetColumn() => $target->entityId,
			$tableHelper->getJudgmentColumn() => $judgmentPage->getId(),
		];
		$dbw->insert( $tableHelper->getLinkTable(), $row, __METHOD__, [ 'IGNORE' ] );
	}

	public function deleteIndex( JudgmentTarget $target, WikiPage $judgmentPage ) {
		$tableHelper = new JudgmentLinkTableHelper( $target->entityType );

		// Delete row linking the judgment and its target.  Select the primary
		// key first, to avoid long queries on the master database.
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$conds = [
			$tableHelper->getTargetColumn() => $target->entityId,
			$tableHelper->getJudgmentColumn() => $judgmentPage->getId(),
		];
		$id = $dbr->selectField(
			$tableHelper->getLinkTable(),
			$tableHelper->getIdColumn(),
			$conds,
			__METHOD__ );

		if ( $id !== false ) {
			$dbw = $this->loadBalancer->getConnection( DB_MASTER );
			$dbw->delete(
				$tableHelper->getLinkTable(),
				[ $tableHelper->getIdColumn() => $id ],
				__METHOD__ );
		}
	}

	public function updateSummary( JudgmentTarget $target, array $summaryValues ) : StatusValue {
		if ( !$summaryValues ) {
			// Nothing to do.
			return Status::newGood();
		}

		$tableHelper = new JudgmentLinkTableHelper( $target->entityType );

		$row = [];
		foreach ( $summaryValues as $key => $value ) {
			$summaryColumn = $tableHelper->getSummaryColumn( $key );
			$row[$summaryColumn] = $value;
		}

		try {
			$dbw = $this->loadBalancer->getConnection( DB_MASTER );
			$dbw->update(
				$tableHelper->getLinkTable(),
				$row,
				[ $tableHelper->getTargetColumn() => $target->entityId ],
				__METHOD__
			);
			return Status::newGood();
		} catch ( DBError $ex ) {
			return Status::newFatal( 'jade-db-error', $ex->getMessage() );
		}
	}

}
