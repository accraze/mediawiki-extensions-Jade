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
namespace Jade\Tests\Hooks;

use Jade\Content\JudgmentContent;
use Jade\Hooks\LinkSummaryHooks;
use Jade\JudgmentEntityType;
use Jade\JudgmentLinkTable;
use Jade\JudgmentTarget;
use Jade\Tests\TestJudgmentLinkAssertions;
use Jade\Tests\TestStorageHelper;
use MediaWikiTestCase;
use Revision;
use Status;
use Title;
use WikiPage;

/**
 * @group Jade
 * @group Database
 * @group medium
 *
 * @coversDefaultClass Jade\Hooks\LinkSummaryHooks
 */
class LinkSummaryHooksTest extends MediaWikiTestCase {

	// Include assertions to test judgment links.
	use TestJudgmentLinkAssertions;

	const DIFF_JUDGMENT = '../../data/valid_diff_judgment.json';
	const REVISION_JUDGMENT = '../../data/valid_revision_judgment.json';

	public function setUp() : void {
		parent::setUp();

		$this->tablesUsed = [
			'jade_diff_judgment',
			'jade_revision_judgment',
			'page',
		];

		$this->mockStorage = $this->getMockBuilder( JudgmentLinkTable::class )
			->disableOriginalConstructor()->getMock();
		$this->setService( 'JadeJudgmentIndexStorage', $this->mockStorage );

		$this->targetRevId = mt_rand();

		$status = JudgmentEntityType::sanitizeEntityType( 'revision' );
		$this->assertTrue( $status->isOK() );
		$this->revisionType = $status->value;

		$this->judgmentPageTitle = Title::newFromText( "Judgment:Revision/{$this->targetRevId}" );

		$this->mockJudgmentPage = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()->getMock();
		$this->mockJudgmentPage->method( 'getTitle' )
			->willReturn( $this->judgmentPageTitle );

		$this->mockRevision = $this->getMockBuilder( Revision::class )
			->disableOriginalConstructor()->getMock();

		$this->user = $this->getTestUser()->getUser();
	}

	/**
	 * @covers ::onPageContentSaveComplete
	 */
	public function testOnPageContentSaveComplete_success() {
		$flags = 0;

		$expectedSummaryValues = [
			'damaging' => true,
			'goodfaith' => false,
		];
		$this->mockStorage->expects( $this->once() )
			->method( 'updateSummary' )
			->with(
				new JudgmentTarget( $this->revisionType, $this->targetRevId ),
				$expectedSummaryValues )
			->willReturn( Status::newGood() );

		$contentText = TestStorageHelper::getJudgmentText( 'diff' );
		LinkSummaryHooks::onPageContentSaveComplete(
			$this->mockJudgmentPage,
			$this->user,
			new JudgmentContent( $contentText ),
			'',
			false,
			false,
			'',
			$flags,
			// FIXME: should be the judgment revision, but ignored for now.
			$this->mockRevision,
			Status::newGood(),
			false,
			0
		);
	}

	// TODO:
	// public function testOnPageContentSaveComplete_badTitle() {
	// public function testOnPageContentSaveComplete_badContent() {
	// public function testOnPageContentSaveComplete_cannotUpdateSummary() {

}
