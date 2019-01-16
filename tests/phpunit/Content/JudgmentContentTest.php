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
namespace Jade\Tests\Content;

use Jade\Content\JudgmentContent;
use Jade\JudgmentValidator;
use Jade\Tests\TestStorageHelper;
use MediaWikiLangTestCase;
use ParserOptions;
use ParserOutput;
use Sanitizer;
use Status;
use StatusValue;
use Title;

/**
 * @group Database
 * @group Jade
 * @group medium
 *
 * @coversDefaultClass Jade\Content\JudgmentContent
 */
class JudgmentContentTest extends MediaWikiLangTestCase {

	public function setUp() {
		parent::setUp();

		// Mock validation.
		$this->mockValidation = $this->getMockBuilder( JudgmentValidator::class )
			->disableOriginalConstructor()
			->getMock();
		$this->setService( 'JadeJudgmentValidator', $this->mockValidation );

		$this->judgmentText = TestStorageHelper::getJudgmentText( 'diff' );
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$content = new JudgmentContent( $this->judgmentText );

		$this->assertEquals(
			JudgmentContent::CONTENT_MODEL_JUDGMENT,
			$content->getModel() );
	}

	/**
	 * @covers ::prepareSave
	 */
	public function testPrepareSave_success() {
		$content = new JudgmentContent( $this->judgmentText );
		$page = $this->getExistingTestPage();
		$user = $this->getTestUser()->getUser();

		$this->mockValidation
			->expects( $this->once() )
			->method( 'validateJudgmentContent' )
			->willReturn( Status::newGood() );
		$this->mockValidation
			->expects( $this->once() )
			->method( 'validatePageTitle' )
			->willReturn( Status::newGood() );

		$status = $content->prepareSave( $page, 0, 0, $user );
		$this->assertTrue( $status->isOK() );
	}

	/**
	 * @covers ::prepareSave
	 */
	public function testPrepareSave_badContent() {
		$content = new JudgmentContent( $this->judgmentText );
		$page = $this->getExistingTestPage();
		$user = $this->getTestUser()->getUser();

		$this->mockValidation
			->expects( $this->once() )
			->method( 'validateJudgmentContent' )
			->willReturn( Status::newFatal( 'jade-bad-content', 'abc' ) );

		$status = $content->prepareSave( $page, 0, 0, $user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-content', $errors[0]['message'] );
	}

	/**
	 * @covers ::prepareSave
	 */
	public function testPrepareSave_badTitle() {
		$content = new JudgmentContent( $this->judgmentText );
		$page = $this->getExistingTestPage();
		$user = $this->getTestUser()->getUser();

		$this->mockValidation
			->expects( $this->once() )
			->method( 'validateJudgmentContent' )
			->willReturn( Status::newGood() );
		$this->mockValidation
			->expects( $this->once() )
			->method( 'validatePageTitle' )
			->willReturn( Status::newFatal( 'jade-bad-entity-type', 'abc' ) );

		$status = $content->prepareSave( $page, 0, 0, $user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-entity-type', $errors[0]['message'] );
	}

	/**
	 * @covers ::isValid
	 */
	public function testIsValid_success() {
		$content = new JudgmentContent( $this->judgmentText );

		$this->mockValidation
			->expects( $this->once() )
			->method( 'validateJudgmentContent' )
			->willReturn( Status::newGood() );

		$this->assertTrue( $content->isValid() );
	}

	public function provideValidationScenarios() {
		yield [
			file_get_contents( __DIR__ . '/../../data/invalid_judgment_bad_json.notjson' ),
			Status::newFatal( 'jade-bad-content-generic' ),
		];
		/* FIXME: HHVM and Zend PHP can't agree on this case, see T207523
		 * yield [
		 * 	'',
		 * 	Status::newFatal( 'jade-bad-content-generic' ),
		 * ];
		 */
		yield [
			'{}',
			Status::newGood(),
		];
		yield [
			TestStorageHelper::getJudgmentText( 'diff' ),
			Status::newFatal( 'abc' ),
			Status::newFatal( 'abc' ),
		];
		yield [
			TestStorageHelper::getJudgmentText( 'diff' ),
			Status::newGood(),
			Status::newGood(),
		];
	}

	/**
	 * @param string $judgmentText Judgment page content to save.
	 * @param StatusValue $expectedStatus Match this result status.
	 * @param StatusValue $injectStatus If not null, cause the mock validator
	 * to return this status.
	 *
	 * @covers ::validateContent
	 * @dataProvider provideValidationScenarios
	 */
	public function testValidateContent( $judgmentText, $expectedStatus, $injectStatus = null ) {
		$content = new JudgmentContent( $judgmentText );

		if ( $injectStatus !== null ) {
			$this->mockValidation
				->expects( $this->once() )
				->method( 'validateJudgmentContent' )
				->willReturn( $injectStatus );
		}

		$status = $content->validateContent();

		$this->assertEquals( $expectedStatus, $status );
	}

	/**
	 * @covers ::isEmpty
	 */
	public function testIsEmpty_empty() {
		$content = new JudgmentContent( '{}' );

		$this->assertTrue( $content->isEmpty() );
	}

	/**
	 * @covers ::isEmpty
	 */
	public function testIsEmpty_notEmpty() {
		$content = new JudgmentContent( $this->judgmentText );

		$this->assertFalse( $content->isEmpty() );
	}

	/**
	 * @covers ::fillParserOutput
	 */
	public function testFillParserOutput_invalid() {
		$content = new JudgmentContent( 'FOO' );
		$output = new ParserOutput;

		$content->fillParserOutput(
			Title::newFromDBkey( 'Judgment:Diff/123' ),
			123,
			ParserOptions::newFromUser( $this->getTestUser()->getUser() ),
			true,
			$output
		);

		$this->assertEquals( '', $output->getRawText() );
	}

	public function provideFillParserOutput() {
		yield [ true, '/Damaging/' ];
		yield [ false, '/^$/' ];
	}

	/**
	 * @covers ::fillParserOutput
	 * @dataProvider provideFillParserOutput
	 */
	public function testFillParserOutput( $doGenerateHtml, $expectedPattern ) {
		$content = new JudgmentContent( $this->judgmentText );
		$output = new ParserOutput;

		$this->mockValidation
			->method( 'validateJudgmentContent' )
			->willReturn( Status::newGood() );
		$this->mockValidation
			->method( 'validatePageTitle' )
			->willReturn( Status::newGood() );

		$content->fillParserOutput(
			Title::newFromDBkey( 'Judgment:Diff/123' ),
			123,
			ParserOptions::newFromUser( $this->getTestUser()->getUser() ),
			$doGenerateHtml,
			$output
		);

		$strippedHtml = Sanitizer::stripAllTags( $output->getText() );
		$this->assertRegExp( $expectedPattern, $strippedHtml );
	}

}
