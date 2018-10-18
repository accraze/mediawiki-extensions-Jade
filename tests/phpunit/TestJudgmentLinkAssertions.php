<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace JADE\Tests;

/**
 * Reusable judgment link assertions.
 */
trait TestJudgmentLinkAssertions {

	/**
	 * Fail if the given link doesn't exist.
	 *
	 * @param int $targetRevisionId rev_id of the target revision.
	 * @param int $judgmentPageId page_id of the judgment page.
	 */
	private function assertJudgmentLink( $entityType, $targetRevisionId, $judgmentPageId ) {
		$result = TestLinkTableHelper::selectJudgmentLink(
			$entityType, $targetRevisionId, $judgmentPageId );
		if ( $result->numRows() < 1 ) {
			$this->fail( 'Judgment link not present.' );
		}
	}

	/**
	 * Fail if the given link exists.
	 *
	 * @param int $targetRevisionId rev_id of the target revision.
	 * @param int $judgmentPageId page_id of the judgment page.
	 */
	private function assertNoJudgmentLink( $entityType, $targetRevisionId, $judgmentPageId ) {
		$result = TestLinkTableHelper::selectJudgmentLink(
			$entityType, $targetRevisionId, $judgmentPageId );
		if ( $result->numRows() > 0 ) {
			$this->fail( 'Judgment link should not be present.' );
		}
	}

}
