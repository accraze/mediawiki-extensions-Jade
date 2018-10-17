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
namespace JADE;

use Wikimedia\Rdbms\LoadBalancer;
use WikiPage;

class JudgmentLinkTable implements JudgmentIndexStorage {

	private $loadBalancer;

	public function __construct( LoadBalancer $loadBalancer ) {
		$this->loadBalancer = $loadBalancer;
	}

	public function insertIndex( JudgmentTarget $target, WikiPage $judgmentPage ) {
		// Get string constants for this target entity type.
		// Note that $target->entityType is sanitized by JudgmentTarget::newGeneric.
		$tableName = JudgmentLinkTableHelper::getLinkTable( $target->entityType );
		$judgmentColumn = JudgmentLinkTableHelper::getJudgmentColumn( $target->entityType );
		$targetColumn = JudgmentLinkTableHelper::getTargetColumn( $target->entityType );

		// Create row linking the judgment and its target.
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );
		$row = [
			$targetColumn => $target->entityId,
			$judgmentColumn => $judgmentPage->getId(),
		];
		$dbw->insert( $tableName, $row, __METHOD__, [ 'IGNORE' ] );
	}

	public function deleteIndex( JudgmentTarget $target, WikiPage $judgmentPage ) {
		// Get string constants for this target entity type.
		// Note that $target->entityType is sanitized by JudgmentTarget::newGeneric.
		$tableName = JudgmentLinkTableHelper::getLinkTable( $target->entityType );
		$judgmentColumn = JudgmentLinkTableHelper::getJudgmentColumn( $target->entityType );
		$targetColumn = JudgmentLinkTableHelper::getTargetColumn( $target->entityType );
		$idColumn = JudgmentLinkTableHelper::getIdColumn( $target->entityType );

		// Delete row linking the judgment and its target.  Select the primary
		// key first, to avoid long queries on the master database.
		$dbr = $this->loadBalancer->getConnection( DB_REPLICA );
		$conds = [
			$targetColumn => $target->entityId,
			$judgmentColumn => $judgmentPage->getId(),
		];
		$id = $dbr->selectField( $tableName, $idColumn, $conds, __METHOD__ );

		if ( $id !== false ) {
			$dbw = $this->loadBalancer->getConnection( DB_MASTER );
			$dbw->delete( $tableName, [ $idColumn => $id ], __METHOD__ );
		}
	}

}
