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
namespace JADE\Tests;

use JADE\JudgmentLinkTableHelper;
use MediaWiki\MediaWikiServices;

/**
 * Break access control as a quick and dirty way to reuse internal string
 * constant functions.  We want to keep the JudgmentLinkTable class closed for
 * production code, since no other classes should know about the implementation
 * details, and we only cheat access in tests.
 */
class TestLinkTableHelper {

	public static function selectJudgmentLink( $entityType, $targetRevisionId, $judgmentPageId ) {
		// Get string constants for this target entity type.
		$tableName = JudgmentLinkTableHelper::getLinkTable( $entityType );
		$judgmentColumn = JudgmentLinkTableHelper::getJudgmentColumn( $entityType );
		$targetColumn = JudgmentLinkTableHelper::getTargetColumn( $entityType );

		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()
			->getConnection( DB_REPLICA );
		$result = $dbr->select(
			[ $tableName ],
			[
				$targetColumn,
				$judgmentColumn,
			],
			[
				$targetColumn => $targetRevisionId,
				$judgmentColumn => $judgmentPageId,
			],
			__METHOD__
		);
		return $result;
	}

}
