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
namespace Jade\Tests;

use Jade\EntityLinkTableHelper;
use Jade\EntityType;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\Assert;

/**
 * Break access control as a quick and dirty way to reuse internal string
 * constant functions.  We want to keep the JudgmentLinkTable class closed for
 * production code, since no other classes should know about the implementation
 * details, and we only cheat access in tests.
 */
class TestLinkTableHelper {

	/**
	 * @param string $entityType entity type identifier
	 * @param int $targetRevisionId revision being judged
	 * @param int $entityPageId page ID being linked
	 * @param array $summaryColumns Any additional summary fields to retrieve.
	 *
	 * @return mixed Native or wrapped database query result.
	 */
	public static function selectJudgmentLink(
		$entityType,
		$targetRevisionId,
		$entityPageId,
		$summaryColumns = []
	) {
		$status = EntityType::sanitizeEntityType( $entityType );
		Assert::assertTrue( $status->isOK() );
		$helper = new EntityLinkTableHelper( $status->value );

		$selectColumns = [
			$helper->getTargetColumn(),
			$helper->getPageColumn(),
		];
		foreach ( $summaryColumns as $schemaName ) {
			$selectColumns[] = $helper->getSummaryColumn( $schemaName );
		}

		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()
			->getConnection( DB_REPLICA );
		$result = $dbr->select(
			[ $helper->getLinkTable() ],
			$selectColumns,
			[
				$helper->getTargetColumn() => $targetRevisionId,
				$helper->getPageColumn() => $entityPageId,
			],
			__METHOD__
		);
		return $result;
	}

}
