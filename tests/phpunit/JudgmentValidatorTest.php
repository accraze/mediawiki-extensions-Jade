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

use FormatJson;
use JADE\JADEServices;
use MediaWikiTestCase;
use Revision;
use StatusValue;
use WikiPage;

const DATA_DIR = '../data';

/**
 * @group JADE
 * @group Database
 *
 * TODO: assert that we're getting the specific, expected error.
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
		yield [ 'invalid_judgment_missing_required.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_bad_json.notjson', 'jade-bad-content' ];
		yield [ 'invalid_judgment_bad_score_data.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_bad_score_schema.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_additional_properties.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_additional_properties2.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_additional_properties3.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_none_preferred.json', 'jade-none-preferred' ];
		yield [ 'invalid_judgment_two_preferred.json', 'jade-too-many-preferred' ];
	}

	// These cases have scoring schemas which aren't allowed for the page title.
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
	public function testInvalidSchemaContent( $path, $expectedError ) {
		$status = $this->runValidation( $path );

		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertEquals( 1, count( $errors ) );
		$this->assertEquals( $expectedError, $errors[0]['message'] );
	}

	/**
	 * @param string $path JSON file with judgment page content to validate.
	 * @return StatusValue validation success or errors.
	 */
	protected function runValidation( $path ) {
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/' . $path );

		$validator = JADEServices::getJudgmentValidator();
		$data = FormatJson::decode( $text );
		return $validator->validateJudgmentContent( $data );
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
		$status = $this->runValidation( $path );
		$this->assertTrue( $status->isOK() );
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

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertTrue( $status->isOK() );
	}

	/**
	 * @dataProvider provideInvalidWithType
	 *
	 * @param string $path Path to test fixture, relative to the test data
	 * directory.
	 * @param string $type Entity type
	 *
	 * @covers JADE\JudgmentValidator::validateEntity
	 * @covers JADE\JudgmentValidator::validateEntitySchema
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 * @covers JADE\JudgmentValidator::parseAndValidateTitle
	 */
	public function testValidatePageTitle_invalidWithType( $path, $type ) {
		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$ucType = ucfirst( $type );
		switch ( $type ) {
			case 'diff':
			case 'revision':
				$title = "{$ucType}/{$revision->getId()}";
				break;
			default:
				$this->fail( "Not handling bad entity type {$type}" );
		}
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/' . $path );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertEquals( 1, count( $errors ) );
		$this->assertEquals( 'jade-illegal-schema', $errors[0]['message'] );
	}

	/**
	 * @covers JADE\JudgmentValidator::parseAndValidateTitle
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 */
	public function testValidatePageTitle_invalidLong() {
		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Revision/{$revision->getId()}/foo";
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/valid_revision_judgment.json' );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertEquals( 1, count( $errors ) );
		$this->assertEquals( 'jade-bad-title-format', $errors[0]['message'] );
	}

	/**
	 * @covers JADE\JudgmentValidator::parseAndValidateTitle
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 */
	public function testValidatePageTitle_invalidShort() {
		$title = 'Revision';
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/valid_diff_judgment.json' );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertEquals( 1, count( $errors ) );
		$this->assertEquals( 'jade-bad-title-format', $errors[0]['message'] );
	}

	/**
	 * @covers JADE\JudgmentValidator::validatePageTitle
	 */
	public function testValidatePageTitle_invalidRevision() {
		// A revision that will "never" exist.  We don't create an entity for this test.
		$title = 'Revision/999999999';
		$text = file_get_contents( __DIR__ . '/' . DATA_DIR . '/valid_diff_judgment.json' );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertEquals( 1, count( $errors ) );
		$this->assertEquals( 'jade-bad-revision-id', $errors[0]['message'] );
	}

}
