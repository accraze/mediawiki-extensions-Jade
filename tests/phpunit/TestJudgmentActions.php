<?php
namespace JADE\Tests;

use ApiTestCase;
use ContentHandler;
use FormatJson;
use WikiPage;
use Title;
use TitleValue;

/**
 * @group JADE
 * @group API
 * @group Database
 * @group medium
 *
 * TODO: Rewrite to use API calls.
 *
 * @covers JADE\ContentHandlers\JudgmentContent
 */
class TestJudgmentActions extends ApiTestCase {
	const REV_JUDGMENT_V1 = '../data/valid_revision_judgment.json';
	const REV_JUDGMENT_V2 = '../data/valid_revision_judgment_v2.json';
	const PAGE_JUDGMENT = '../data/valid_page_judgment.json';

	public function setUp() {
		// Needs to be before setup since this gets cached
		$this->mergeMwGlobalArrayValue(
			'wgGroupPermissions',
			[ 'sysop' => [ 'deleterevision' => true ] ]
		);

		parent::setUp();

		$this->tablesUsed[] = 'recentchanges';
		$this->tablesUsed[] = 'page';
	}

	/**
	 * @covers JADE\ContentHandlers\JudgmentContent::prepareSave
	 * @covers JADE\ContentHandlers\JudgmentContent::isValid
	 */
	public function testCreateRevisionJudgment() {
		// Create target page.
		$article = $this->makeEdit(
			0, 'TestJudgmentActionsPage', 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgmentText = $this->mutateEntity( $judgmentText, $page_id, $rev_id );
		$judgment = $this->makeEdit(
			NS_JADE,
			"Revision/{$rev_id}",
			$judgmentText,
			'summary says'
		);
	}

	/**
	 * @covers JADE\ContentHandlers\JudgmentContent::prepareSave
	 * @covers JADE\ContentHandlers\JudgmentContent::isValid
	 */
	public function testUpdateRevisionJudgment() {
		// Create target page.
		$article = $this->makeEdit(
			0, 'TestJudgmentActionsPage', 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create initial revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgmentText = $this->mutateEntity( $judgmentText, $page_id, $rev_id );
		$judgment = $this->makeEdit(
			NS_JADE,
			"Revision/{$rev_id}",
			$judgmentText,
			'summary says'
		);

		// Update the judgment.
		$judgment2Text = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V2 );
		$judgment2Text = $this->mutateEntity( $judgment2Text, $page_id, $rev_id );
		$judgment2 = $this->makeEdit(
			NS_JADE,
			"Revision/{$rev_id}",
			$judgment2Text,
			'summary says'
		);
	}

	public function testSuppressUnsuppressRevisionJudgment() {
		// Create target page.
		$article = $this->makeEdit(
			0, 'TestJudgmentActionsPage', 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create initial revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgmentText = $this->mutateEntity( $judgmentText, $page_id, $rev_id );
		$judgment = $this->makeEdit(
			NS_JADE,
			"Revision/{$rev_id}",
			$judgmentText,
			'summary says'
		);

		// Update the judgment.
		$judgment2Text = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V2 );
		$judgment2Text = $this->mutateEntity( $judgment2Text, $page_id, $rev_id );
		$judgment2 = $this->makeEdit(
			NS_JADE,
			"Revision/{$rev_id}",
			$judgment2Text,
			'summary says'
		);

		// Suppress the first edit.
		$sysop = $this->getTestSysop()->getUser();
		$out = $this->doApiRequest( [
			'action' => 'revisiondelete',
			'type' => 'revision',
			'target' => $judgment['page']->getTitle()->getDbKey(),
			'ids' => $judgment['revision']->getId(),
			'hide' => 'content|user|comment',
			'token' => $sysop->getEditToken(),
		] );
		// Check the output
		$out = $out[0]['revisiondelete'];
		$this->assertEquals( $out['status'], 'Success' );

		// Unsuppress the first edit.
		$out = $this->doApiRequest( [
			'action' => 'revisiondelete',
			'type' => 'revision',
			'target' => $judgment['page']->getTitle()->getDbKey(),
			'ids' => $judgment['revision']->getId(),
			'show' => 'content|user|comment',
			'token' => $sysop->getEditToken(),
		] );
		// Check the output
		$out = $out[0]['revisiondelete'];
		$this->assertEquals( $out['status'], 'Success' );
	}

	/**
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 */
	public function testCreateJudgment_badTitleRevId() {
		// Create target page.
		$article = $this->makeEdit(
			0,
			'TestJudgmentActionsPage',
			'abcdef',
			'some summary'
		);
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgmentText = $this->mutateEntity( $judgmentText, $page_id, $rev_id );
		$bad_rev_id = $rev_id + 1;
		$judgment = $this->makeEdit(
			NS_JADE,
			"Revision/{$bad_rev_id}",
			$judgmentText,
			'summary says',
			false
		);
	}

	/**
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 */
	public function testCreateJudgment_badTitlePageId() {
		// Create target page.
		$article = $this->makeEdit(
			0, 'TestJudgmentActionsPage', 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::PAGE_JUDGMENT );
		$judgmentText = $this->mutateEntity( $judgmentText, $page_id, $rev_id );
		$bad_page_id = $page_id + 1;
		$judgment = $this->makeEdit(
			NS_JADE,
			"Page/{$bad_page_id}",
			$judgmentText,
			'summary says',
			false
		);
	}

	/**
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 */
	public function testCreateJudgment_badTitleType() {
		// Create target page.
		$article = $this->makeEdit(
			0, 'TestJudgmentActionsPage', 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgmentText = $this->mutateEntity( $judgmentText, $page_id, $rev_id );
		$judgment = $this->makeEdit(
			NS_JADE,
			"Page/{$rev_id}",
			$judgmentText,
			'summary says',
			false
		);
	}

	/**
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 */
	public function testCreateJudgment_badTitleFormat() {
		// Create target page.
		$article = $this->makeEdit(
			0, 'TestJudgmentActionsPage', 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgmentText = $this->mutateEntity( $judgmentText, $page_id, $rev_id );
		$judgment = $this->makeEdit(
			NS_JADE,
			"Page/{$rev_id}/Wrongunder",
			$judgmentText,
			'summary says',
			false
		);
	}

	protected function makeEdit( $namespace, $title, $content, $summary, $expectedStatus = true ) {
		$editTarget = new TitleValue( $namespace, $title );
		$title = Title::newFromLinkTarget( $editTarget );
		$page = WikiPage::factory( $title );
		$user = $this->getTestUser()->getUser();
		$status = $page->doEditContent(
			ContentHandler::makeContent( $content, $title ),
			$summary,
			0,
			false,
			$user
		);

		$this->assertEquals( $expectedStatus, $status->isGood() );
		if ( $expectedStatus === false ) {
			// Nothing more to do.
			return;
		}

		$revision = $status->value["revision"];
		$this->assertNotNull( $revision );

		return [
			"page" => $page,
			"revision" => $revision,
		];
	}

	public function mutateEntity( $text, $page_id, $rev_id ) {
		$status = FormatJson::parse( $text );
		$parsed = $status->value;

		if ( $parsed->entity->type === 'diff'
			|| $parsed->entity->type === 'revision'
		) {
			$parsed->entity->rev_id = $rev_id;
		} else {
			$parsed->entity->page_id = $page_id;
		}

		return FormatJson::encode( $parsed );
	}

}
