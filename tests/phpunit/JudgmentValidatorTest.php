<?php
namespace JADE\Tests;

use ContentHandler;
use FormatJson;
use MediaWikiTestCase;
use Revision;
use Title;
use TitleValue;
use WikiPage;

use JADE\ContentHandlers\JudgmentContent;

const DATA_DIR = '../data';

/**
 * @group JADE
 * @group Database
 *
 * @covers JADE\JudgmentValidator
 */
class JudgmentValidatorTest extends MediaWikiTestCase {

	/**
	 * @var WikiPage|null
	 */
	private $page = null;

	/**
	 * @var Revision
	 */
	private $revision;

	public function provideImmediatelyInvalidContent() {
		yield [ 'invalid_judgment_bad_score_schema.json' ];
		yield [ 'invalid_judgment_disallowed_score_schema.json' ];
		yield [ 'invalid_judgment_missing_required.json' ];
		yield [ 'invalid_judgment_bad_type.json' ];
		yield [ 'invalid_judgment_bad_json.notjson' ];
		yield [ 'invalid_judgment_additional_properties.json' ];
	}

	public function provideValidJudgments() {
		yield [ 'valid_page_judgment.json' ];
		yield [ 'valid_diff_judgment.json' ];
		yield [ 'valid_revision_judgment.json' ];
	}

	/**
	 * @dataProvider provideImmediatelyInvalidContent
	 *
	 * @param string $path Path to test fixture, relative to the test data
	 * directory.
	 *
	 * @covers JADE\JudgmentValidator::validateJudgmentContent
	 * @covers JADE\JudgmentValidator::validateBasicSchema
	 * @covers JADE\JudgmentValidator::validateScoreSchemas
	 */
	public function testImmediatelyInvalidContent( $path ) {
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/' . $path );
		$obj = new JudgmentContent( $text );
		$this->assertEquals( false, $obj->isValid() );
	}

	/**
	 * @dataProvider provideValidJudgments
	 *
	 * @param string $path Path to test fixture, relative to the test data
	 * directory.
	 *
	 * @covers JADE\JudgmentValidator::validateEntity
	 */
	public function testInvalidEntity( $path ) {
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/' . $path );

		$text = $this->mutateEntity( $text, false );

		$obj = new JudgmentContent( $text );
		$this->assertEquals( false, $obj->isValid() );
	}

	/**
	 * @dataProvider provideValidJudgments
	 *
	 * @param string $path Path to test fixture, relative to the test data
	 * directory.
	 *
	 * @covers JADE\JudgmentValidator::validateEntity
	 */
	public function testValidEntity( $path ) {
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/' . $path );

		$text = $this->mutateEntity( $text, true );

		$obj = new JudgmentContent( $text );
		$this->assertEquals( true, $obj->isValid() );
	}

	protected function makeEdit() {
		static $counter = 0;

		$this->tablesUsed[] = 'recentchanges';
		$this->tablesUsed[] = 'page';

		$editTarget = new TitleValue( 0, 'JadeJudgmentContentTestPage' );
		$title = Title::newFromLinkTarget( $editTarget );
		$summary = 'Test edit';
		$this->page = WikiPage::factory( $title );
		$user = $this->getTestUser()->getUser();
		$status = $this->page->doEditContent(
			ContentHandler::makeContent( __CLASS__ . $counter++, $title ),
			$summary,
			0,
			false,
			$user
		);

		$this->assertTrue( $status->isGood() );

		$this->revision = $status->value['revision'];
		$this->assertNotNull( $this->revision );
	}

	public function mutateEntity( $text, $makeValid ) {
		$this->makeEdit();

		$status = FormatJson::parse( $text );
		$parsed = $status->value;

		if ( $parsed->entity->type === 'diff'
			|| $parsed->entity->type === 'revision'
		) {
			if ( $makeValid ) {
				$parsed->entity->rev_id = $this->revision->getId();
			} else {
				$parsed->entity->rev_id = $this->revision->getId() + 1;
			}
		} else {
			if ( $makeValid ) {
				$parsed->entity->page_id = $this->page->getId();
			} else {
				$parsed->entity->page_id = $this->page->getId() + 1;
			}
		}

		return FormatJson::encode( $parsed );
	}

}
