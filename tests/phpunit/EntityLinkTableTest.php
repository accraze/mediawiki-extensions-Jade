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

use Jade\EntityLinkTable;
use Jade\ProposalEntityType;
use Jade\ProposalTarget;
use Jade\TitleHelper;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;

/**
 * @group Jade
 * @group Database
 * @group medium
 *
 * @coversDefaultClass \Jade\EntityLinkTable
 * @covers ::__construct
 */
class EntityLinkTableTest extends MediaWikiTestCase {

	// Include assertions to test judgment links.
	// use TestJudgmentLinkAssertions;

	public function setUp() : void {
		parent::setUp();
		$this->markTestSkipped( 'not in use' );
		$this->tablesUsed = [
			'jade_diff_judgment',
			'jade_revision_judgment',
			'page',
		];

		// Content article and revision fixtures.
		$article = TestStorageHelper::makeEdit(
			NS_MAIN,
			__CLASS__ . strval( mt_rand() ) );
		$this->page = $article['page'];
		$this->revision = $article['revision'];

		/*
		 * Note: Normal clients should fetch the service rather than directly
		 * instantiating.
		 *     $storage = JadeServices::getJudgmentIndexStorage();
		 */
		$this->indexStorage = new EntityLinkTable(
			MediaWikiServices::getInstance()->getDBLoadBalancer() );

		// Disable all hooks, so that judgment links can only be altered manually.
		$this->setMwGlobals( [
			'wgHooks' => [],
		] );
	}

	/**
	 * @param string $entityType
	 * @param int $entityId
	 */
	private function createJudgment( $entityType, $entityId ) {
		$status = ProposalEntityType::sanitizeEntityType( $entityType );
		$this->assertTrue( $status->isOK() );
		$target = new ProposalTarget( $status->value, $entityId );
		$title = TitleHelper::buildJadeTitle( $target );
		$judgmentText = TestStorageHelper::getJudgmentText( $entityType );
		$judgmentStatus = TestStorageHelper::makeEdit(
			NS_JADE, $title->getDBkey(), $judgmentText );
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

	/**
	 * @covers ::updateSummary
	 */
	public function testUpdateSummary_normal() {
		$judgment = $this->createJudgment( 'diff', $this->revision->getId() );

		// Prepare the row.
		$this->indexStorage->insertIndex( $judgment['target'], $judgment['judgmentPage'] );
		$this->assertJudgmentLink( 'diff', $this->revision->getId(), $judgment['judgmentPage']->getId() );

		$this->indexStorage->updateSummary( $judgment['target'], [
			'damaging' => true,
			'goodfaith' => false,
		] );

		$result = TestLinkTableHelper::selectJudgmentLink(
			'diff',
			$this->revision->getId(),
			$judgment['judgmentPage']->getId(),
			[ 'damaging', 'goodfaith' ] );
		$this->assertEquals( 1, $result->numRows() );
		foreach ( $result as $row ) {
			$this->assertEquals( 1, $row->jaded_damaging );
			$this->assertSame( 0, $row->jaded_goodfaith );
		}
	}

}
