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

namespace JADE\Tests\Maintenance;

use JADE\Maintenance\CleanJudgmentLinks;
use JADE\Tests\TestJudgmentLinkAssertions;
use JADE\Tests\TestStorageHelper;
use MediaWiki\Tests\Maintenance\MaintenanceBaseTestCase;
use Revision;
use WikiPage;

/**
 * @group JADE
 * @group Database
 * @group medium
 * @covers JADE\Maintenance\CleanJudgmentLinks
 * @coversDefaultClass JADE\Maintenance\CleanJudgmentLinks
 */
class CleanJudgmentLinksTest extends MaintenanceBaseTestCase {

	// Include assertions to test judgment links.
	use TestJudgmentLinkAssertions;

	private $realService;

	public function getMaintenanceClass() {
		return CleanJudgmentLinks::class;
	}

	public function setUp() {
		parent::setUp();
		$this->tablesUsed[] = 'jade_diff_judgment';
		$this->tablesUsed[] = 'jade_revision_judgment';
	}

	private function getDiffJudgmentContent() {
		return file_get_contents( __DIR__ . '/../../data/valid_diff_judgment.json' );
	}

	private function getRevisionJudgmentContent() {
		return file_get_contents( __DIR__ . '/../../data/valid_revision_judgment.json' );
	}

	private function createRevision() {
		list( $page, $revision ) = TestStorageHelper::createEntity();
		return $revision;
	}

	private function createDiffJudgment( Revision $revision ) {
		$status = TestStorageHelper::saveJudgment(
			"Diff/{$revision->getId()}",
			$this->getDiffJudgmentContent()
		);
		$this->assertTrue( $status->isOK() );
		$page = WikiPage::newFromID( $status->value['revision']->getPage() );
		return $page;
	}

	private function createRevisionJudgment( Revision $revision ) {
		$status = TestStorageHelper::saveJudgment(
			"Revision/{$revision->getId()}",
			$this->getRevisionJudgmentContent()
		);
		$this->assertTrue( $status->isOK() );
		$page = WikiPage::newFromID( $status->value['revision']->getPage() );
		return $page;
	}

	/**
	 * Make sure that the starting state has no judgment link rows.
	 */
	public function testEmptyNoLinks() {
		$dbr = wfGetDB( DB_REPLICA );
		$result = $dbr->select(
			[ 'jade_diff_judgment' ],
			[ 'jaded_id' ],
			null,
			__METHOD__
		);
		$this->assertEquals( 0, $result->numRows() );

		$result = $dbr->select(
			[ 'jade_revision_judgment' ],
			[ 'jader_id' ],
			null,
			__METHOD__
		);
		$this->assertEquals( 0, $result->numRows() );
	}

	/**
	 * @covers ::findAndDeleteOrphanedLinks
	 * @covers ::findOrphanedLinks
	 * @covers ::deleteOrphanedLinks
	 * TODO: merge with Revision test
	 */
	public function testDeleteOrphanedDiffLinks() {
		// Create diff judgment and link.
		$revision = $this->createRevision();
		$page = $this->createDiffJudgment( $revision );
		$pageId = $page->getId();

		// Orphan it by deleting the judgment page, disabling the hook
		// which would normally clean up the link.
		$this->setTemporaryHook( 'ArticleDeleteComplete', false );
		$page->doDeleteArticleReal( 'reasonable' );

		// Check that the link still exists.
		$this->assertJudgmentLink( 'diff', $revision->getId(), $pageId );

		// Run the job.
		$this->maintenance->loadParamsAndArgs();
		$this->maintenance->execute();

		// Check that the link was deleted.
		$this->assertNoJudgmentLink( 'diff', $revision->getId(), $page->getId() );
	}

	/**
	 * @covers ::findAndDeleteOrphanedLinks
	 * @covers ::findOrphanedLinks
	 * @covers ::deleteOrphanedLinks
	 */
	public function testDeleteOrphanedRevisionLinks() {
		// Create revision judgment and link.
		$revision = $this->createRevision();
		$page = $this->createRevisionJudgment( $revision );
		$pageId = $page->getId();

		// Orphan it by deleting the judgment page, disabling the hook
		// which would normally clean up the link.
		$this->setTemporaryHook( 'ArticleDeleteComplete', false );
		$page->doDeleteArticleReal( 'reasonable' );

		// Check that the link still exists.
		$this->assertJudgmentLink( 'revision', $revision->getId(), $pageId );

		// Run the job.
		$this->maintenance->loadParamsAndArgs();
		$this->maintenance->execute();

		// Check that the link was deleted.
		$this->assertNoJudgmentLink( 'revision', $revision->getId(), $page->getId() );
	}

	/**
	 * @covers ::findAndConnectUnlinkedJudgments
	 * @covers ::findUnlinkedJudgments
	 * @covers ::connectUnlinkedJudgments
	 */
	public function testConnectUnlinkedDiffJudgments() {
		// Create diff judgment without link.
		$this->setTemporaryHook( 'PageContentInsertComplete', false );
		$revision = $this->createRevision();
		$page = $this->createDiffJudgment( $revision );

		// Check that no link was created.
		$this->assertNoJudgmentLink( 'diff', $revision->getId(), $page->getId() );

		// Run the job.
		$this->maintenance->loadParamsAndArgs();
		$this->maintenance->execute();

		$this->assertJudgmentLink( 'diff', $revision->getId(), $page->getId() );
	}

	/**
	 * @covers ::findAndConnectUnlinkedJudgments
	 * @covers ::findUnlinkedJudgments
	 * @covers ::connectUnlinkedJudgments
	 */
	public function testConnectUnlinkedRevisionJudgments() {
		// Create revision judgment without link.
		$this->setTemporaryHook( 'PageContentInsertComplete', false );
		$revision = $this->createRevision();
		$page = $this->createRevisionJudgment( $revision );

		// Check that no link was created.
		$this->assertNoJudgmentLink( 'revision', $revision->getId(), $page->getId() );

		// Run the job.
		$this->maintenance->loadParamsAndArgs();
		$this->maintenance->execute();

		$this->assertJudgmentLink( 'revision', $revision->getId(), $page->getId() );
	}

}
