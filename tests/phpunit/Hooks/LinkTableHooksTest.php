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

use JADE\JudgmentEntityType;
use JADE\Hooks\LinkTableHooks;
use JADE\JudgmentLinkTable;
use JADE\JudgmentTarget;
use JADE\Tests\TestJudgmentLinkAssertions;
use LogEntry;
use MediaWikiTestCase;
use Revision;
use TextContent;
use Title;
use TitleValue;
use Wikimedia\TestingAccessWrapper;
use WikiPage;

/**
 * @group JADE
 * @group Database
 * @group medium
 *
 * @coversDefaultClass JADE\Hooks\LinkTableHooks
 */
class LinkTableHooksTest extends MediaWikiTestCase {

	// Include assertions to test judgment links.
	use TestJudgmentLinkAssertions;

	const DIFF_JUDGMENT = '../../data/valid_diff_judgment.json';
	const REVISION_JUDGMENT = '../../data/valid_revision_judgment.json';

	public function setUp() {
		parent::setUp();

		$this->tablesUsed = [
			'jade_diff_judgment',
			'jade_revision_judgment',
			'page',
		];

		$this->mockStorage = $this->getMockBuilder( JudgmentLinkTable::class )
			->disableOriginalConstructor()->getMock();
		$this->setService( 'JADEJudgmentIndexStorage', $this->mockStorage );

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

		$this->mockLogEntry = $this->getMockBuilder( LogEntry::class )
			->disableOriginalConstructor()->getMock();

		$this->user = $this->getTestUser()->getUser();
	}

	/**
	 * @covers ::onPageContentInsertComplete
	 */
	public function testOnPageContentInsertComplete_success() {
		$flags = 0;

		$this->mockStorage->expects( $this->once() )
			->method( 'insertIndex' )
			->with( new JudgmentTarget( $this->revisionType, $this->targetRevId ), $this->mockJudgmentPage );

		LinkTableHooks::onPageContentInsertComplete(
			$this->mockJudgmentPage,
			$this->user,
			new TextContent( '' ),
			'',
			false,
			false,
			'',
			$flags,
			$this->mockRevision
		);
	}

	/**
	 * @covers ::onPageContentInsertComplete
	 */
	public function testOnPageContentInsertComplete_noTarget() {
		$flags = 0;

		$this->mockStorage->expects( $this->never() )
			->method( 'insertIndex' );

		$nonJudgmentPage = $this->getExistingTestPage( __METHOD__ );
		LinkTableHooks::onPageContentInsertComplete(
			$nonJudgmentPage,
			$this->user,
			new TextContent( '' ),
			'',
			false,
			false,
			'',
			$flags,
			$this->mockRevision
		);
	}

	/**
	 * @covers ::onArticleDeleteComplete
	 */
	public function testOnArticleDeleteComplete_success() {
		$this->mockStorage->expects( $this->once() )
			->method( 'deleteIndex' )
			->with( new JudgmentTarget( $this->revisionType, $this->targetRevId ), $this->mockJudgmentPage );

		LinkTableHooks::onArticleDeleteComplete(
			$this->mockJudgmentPage,
			$this->user,
			'',
			321,
			new TextContent( '' ),
			$this->mockLogEntry
		);
	}

	/**
	 * @covers ::onArticleDeleteComplete
	 */
	public function testOnArticleDeleteComplete_noTarget() {
		$this->mockStorage->expects( $this->never() )
			->method( 'deleteIndex' );

		$nonJudgmentPage = $this->getExistingTestPage( __METHOD__ );
		LinkTableHooks::onArticleDeleteComplete(
			$nonJudgmentPage,
			$this->user,
			'',
			321,
			new TextContent( '' ),
			$this->mockLogEntry
		);
	}

	/**
	 * @covers ::onArticleUndelete
	 */
	public function testOnArticleUndelete_success() {
		$page = $this->getExistingTestPage();
		$pageId = $page->getId();

		$this->mockStorage->expects( $this->once() )
			->method( 'insertIndex' )
			->with(
				new JudgmentTarget( $this->revisionType, $this->targetRevId ),
				$this->callback( function ( $page ) use ( $pageId ) {
					return $page->getId() === $pageId;
				} )
			);

		LinkTableHooks::onArticleUndelete(
			$this->judgmentPageTitle,
			true,
			'',
			$pageId,
			[ $pageId => true ]
		);
	}

	/**
	 * @covers ::onArticleUndelete
	 */
	public function testOnArticleUndelete_noTarget() {
		$page = $this->getExistingTestPage();
		$pageId = $page->getId();

		$this->mockStorage->expects( $this->never() )
			->method( 'insertIndex' );

		// Non-judgment page.
		LinkTableHooks::onArticleUndelete(
			$page->getTitle(),
			true,
			'',
			$pageId,
			[ $pageId => true ]
		);
	}

	/**
	 * @covers ::onArticleUndelete
	 */
	public function testOnArticleUndelete_nocreate() {
		$page = $this->getExistingTestPage();
		$pageId = $page->getId();

		$this->mockStorage->expects( $this->never() )
			->method( 'insertIndex' );

		LinkTableHooks::onArticleUndelete(
			$this->judgmentPageTitle,
			false,
			'',
			$pageId,
			[ $pageId => true ]
		);
	}

	public function provideTargets() {
		$diffType = JudgmentEntityType::sanitizeEntityType( 'diff' )->value;
		yield [
			NS_JUDGMENT,
			'Diff/123',
			new JudgmentTarget( $diffType, 123 ),
		];
		yield [
			NS_JUDGMENT,
			'Diff/FOO',
			null,
		];
		yield [
			NS_MAIN,
			'No_page' . strval( mt_rand() ),
			null,
		];
	}

	/**
	 * @covers ::judgmentTarget
	 * @dataProvider provideTargets
	 */
	public function testJudgmentTarget( $namespace, $titleStr, $expectedTarget ) {
		$hooksStatic = TestingAccessWrapper::newFromClass( LinkTableHooks::class );
		$title = new TitleValue( $namespace, $titleStr );
		$target = $hooksStatic->judgmentTarget( $title );

		$this->assertEquals( $expectedTarget, $target );
	}

}
