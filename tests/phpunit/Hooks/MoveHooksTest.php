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
namespace JADE\Tests\Hooks;

use ApiTestCase;
use ApiUsageException;
use JADE\Tests\TestStorageHelper;

/**
 * @group API
 * @group Database
 * @group JADE
 * @group medium
 *
 * @covers JADE\Hooks\MoveHooks
 */
class MoveHooksTest extends ApiTestCase {
	const DIFF_JUDGMENT = '../../data/valid_diff_judgment.json';

	public function setUp() {
		parent::setUp();
		$this->tablesUsed = [
			'page',
			'jade_diff_judgment',
			'jade_revision_judgment',
		];
	}

	/**
	 * @covers JADE\Hooks\MoveHooks::onMovePageIsValidMove
	 */
	public function testOnMovePageIsValidMove() {
		// Create target page.
		$article = TestStorageHelper::makeEdit(
			NS_MAIN, 'TestJudgmentActionsPage', 'abcdef', 'some summary' );
		$rev_id = $article['revision']->getId();

		// Create diff judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::DIFF_JUDGMENT );
		$judgment = TestStorageHelper::makeEdit(
			NS_JUDGMENT,
			"Diff/{$rev_id}",
			$judgmentText,
			'summary says'
		);
		$this->assertNotNull( $judgment['page'] );
		$this->assertNotNull( $judgment['revision'] );

		$oldTitle = "Judgment:Diff/{$rev_id}";
		$newTitle = 'Judgment:Diff/' . strval( $rev_id + 1 );

		// FIXME: fragile.
		$this->setExpectedException( ApiUsageException::class,
			'Moving judgment pages is not allowed.' );

		$result = $this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $oldTitle,
			'to' => $newTitle,
		] );
	}

}
