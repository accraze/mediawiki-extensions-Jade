<?php
namespace JADE\Tests;

use MediaWikiTestCase;
use Revision;
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

	public function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'recentchanges';
		$this->tablesUsed[] = 'revision';

		$this->user = $this->getTestUser()->getUser();
	}

	public function provideInvalidSchemaContent() {
		yield [ 'invalid_judgment_missing_required.json' ];
		yield [ 'invalid_judgment_bad_json.notjson' ];
		yield [ 'invalid_judgment_bad_score_data.json' ];
		yield [ 'invalid_judgment_bad_score_schema.json' ];
		yield [ 'invalid_judgment_additional_properties.json' ];
		yield [ 'invalid_judgment_additional_properties2.json' ];
		yield [ 'invalid_judgment_additional_properties3.json' ];
		yield [ 'invalid_judgment_two_preferred.json' ];
	}

	// Will be invalid only when we know the page title.
	public function provideInvalidWithType() {
		yield [ 'invalid_judgment_disallowed_score_schema.json', 'diff' ];
	}

	public function provideValidJudgments() {
		yield [ 'valid_diff_judgment.json', 'diff' ];
		yield [ 'valid_revision_judgment.json', 'revision' ];
	}

	/**
	 * @dataProvider provideInvalidSchemaContent
	 *
	 * @param string $path Path to test fixture, relative to the test data
	 * directory.
	 *
	 * @covers JADE\JudgmentValidator::validateBasicSchema
	 * @covers JADE\JudgmentValidator::validateJudgmentContent
	 * @covers JADE\JudgmentValidator::validatePreferred
	 */
	public function testInvalidSchemaContent( $path ) {
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
	 * @covers JADE\JudgmentValidator::validateBasicSchema
	 * @covers JADE\JudgmentValidator::validateJudgmentContent
	 * @covers JADE\JudgmentValidator::validatePreferred
	 */
	public function testValidateJudgmentContent( $path ) {
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/' . $path );

		$obj = new JudgmentContent( $text );
		$this->assertEquals( true, $obj->isValid() );
	}

	/**
	 * @dataProvider provideValidJudgments
	 *
	 * @covers JADE\JudgmentValidator::parseAndValidateTitle
	 * @covers JADE\JudgmentValidator::validateEntity
	 * @covers JADE\JudgmentValidator::validateEntitySchema
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 */
	public function testValidatePageTitle_valid( $path, $type ) {
		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$ucType = ucfirst( $type );
		$title = "{$ucType}/{$revision->getId()}";
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/' . $path );

		$success = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertTrue( $success );
	}

	/**
	 * @dataProvider provideInvalidWithType
	 *
	 * @param string $path Path to test fixture, relative to the test data
	 * directory.
	 * @param string $type Entity type (ignored here)
	 *
	 * @covers JADE\JudgmentValidator::validateEntity
	 * @covers JADE\JudgmentValidator::validateEntitySchema
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 * @covers JADE\JudgmentValidator::parseAndValidateTitle
	 */
	public function testValidatePageTitle_invalidWithType( $path, $type ) {
		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$ucType = ucfirst( $type );
		if ( $type === 'page' ) {
			$title = "{$ucType}/{$page->getId()}";
		} else {
			$title = "{$ucType}/{$revision->getId()}";
		}
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/' . $path );

		$success = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $success );
	}

	/**
	 * @covers JADE\JudgmentValidator::parseAndValidateTitle
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 */
	public function testValidatePageTitle_invalidLong() {
		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Revision/{$revision->getId()}/foo";
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/valid_revision_judgment.json' );

		$success = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $success );
	}

	/**
	 * @covers JADE\JudgmentValidator::parseAndValidateTitle
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 */
	public function testValidatePageTitle_invalidShort() {
		$title = 'Revision/';
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/valid_diff_judgment.json' );

		$success = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $success );
	}

}
