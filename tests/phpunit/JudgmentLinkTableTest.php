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

use JADE\JudgmentLinkTable;
use JADE\JudgmentTarget;
use JADE\TitleHelper;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;

/**
 * @group JADE
 * @group Database
 * @group medium
 *
 * @coversDefaultClass JADE\JudgmentLinkTable
 * @covers ::__construct
 */
class JudgmentLinkTableTest extends MediaWikiTestCase {

	// Include assertions to test judgment links.
	use TestJudgmentLinkAssertions;

	public function setUp() {
		parent::setUp();

		$this->tablesUsed = [
			'jade_diff_judgment',
			'jade_revision_judgment',
			'page',
		];

		// Content article and revision fixtures.
		$article = TestStorageHelper::makeEdit(
			NS_MAIN,
			'JudgmentLinkTableTest' . strval( mt_rand() ) );
		$this->page = $article['page'];
		$this->revision = $article['revision'];

		/*
		 * Note: Normal clients should fetch the service rather than directly
		 * instantiating.
		 *     $storage = JADEServices::getJudgmentIndexStorage();
		 */
		$this->indexStorage = new JudgmentLinkTable(
			MediaWikiServices::getInstance()->getDBLoadBalancer() );

		// Disable all hooks, so that judgment links can only be altered manually.
		$this->setMwGlobals( [
			'wgHooks' => [],
		] );
	}

	private function createJudgment( $entityType, $entityId ) {
		$target = JudgmentTarget::newGeneric( $entityType, $entityId );
		$title = TitleHelper::buildJadeTitle( $target )->value;
		$judgmentText = TestStorageHelper::getJudgmentText( $entityType );
		$judgmentStatus = TestStorageHelper::makeEdit(
			NS_JUDGMENT, $title->getDBkey(), $judgmentText );
		$this->assertNotNull( $judgmentStatus['page'] );
		$this->assertNotNull( $judgmentStatus['revision'] );

		return [
			'target' => $target,
			'judgmentPage' => $judgmentStatus['page'],
		];
	}

	public function provideEntityTypes() {
		global $wgJadeEntityTypeNames;

		$types = array_keys( $wgJadeEntityTypeNames );
		foreach ( $types as $type ) {
			yield [ $type ];
		}
	}

	/**
	 * Test that this class is preparing fixtures as expected.
	 *
	 * @coversNothing
	 */
	public function testFixtures() {
		$judgment = $this->createJudgment( 'diff', $this->revision->getId() );

		// Didn't create a link.
		$this->assertNoJudgmentLink(
			'diff', $this->revision->getId(), $judgment['judgmentPage']->getId() );
	}

	/**
	 * @covers ::insertIndex
	 * @dataProvider provideEntityTypes
	 */
	public function testInsertIndex_normal( $type ) {
		$judgment = $this->createJudgment( $type, $this->revision->getId() );

		$this->indexStorage->insertIndex( $judgment['target'], $judgment['judgmentPage'] );

		$this->assertJudgmentLink( $type, $this->revision->getId(), $judgment['judgmentPage']->getId() );
	}

	/**
	 * @covers ::insertIndex
	 * @dataProvider provideEntityTypes
	 */
	public function testInsertIndex_duplicate( $type ) {
		$judgment = $this->createJudgment( $type, $this->revision->getId() );

		$this->indexStorage->insertIndex( $judgment['target'], $judgment['judgmentPage'] );
		// Duplicate.  Shouldn't throw an error.
		$this->indexStorage->insertIndex( $judgment['target'], $judgment['judgmentPage'] );

		// There can be only one.
		$result = TestLinkTableHelper::selectJudgmentLink(
			$type, $this->revision->getId(), $judgment['judgmentPage']->getId() );
		$this->assertEquals( 1, $result->numRows() );
	}

	/**
	 * @covers ::insertIndex
	 * @dataProvider provideEntityTypes
	 * @expectedException Wikimedia\Rdbms\DBQueryError
	 */
	public function testInsertIndex_failure( $type ) {
		// Cheap way to cause a database failure.
		$badTarget = TestStorageHelper::getBadTarget( $this );

		// Unrelated judgment, just to have a real WikiPage.
		$judgment = $this->createJudgment( $type, $this->revision->getId() );

		// Should fail with a database exception.
		$this->indexStorage->insertIndex( $badTarget, $judgment['judgmentPage'] );
	}

	/**
	 * @covers ::deleteIndex
	 * @dataProvider provideEntityTypes
	 */
	public function testDeleteIndex_normal( $type ) {
		$judgment = $this->createJudgment( $type, $this->revision->getId() );
		$this->indexStorage->insertIndex( $judgment['target'], $judgment['judgmentPage'] );

		$this->indexStorage->deleteIndex( $judgment['target'], $judgment['judgmentPage'] );

		$this->assertNoJudgmentLink(
			$type, $this->revision->getId(), $judgment['judgmentPage']->getId() );
	}

	/**
	 * @covers ::deleteIndex
	 * @dataProvider provideEntityTypes
	 */
	public function testDeleteIndex_missing( $type ) {
		$judgment = $this->createJudgment( $type, $this->revision->getId() );

		// No index row yet.

		// Ignores the failure, DWIM.
		$this->indexStorage->deleteIndex( $judgment['target'], $judgment['judgmentPage'] );
	}

}
