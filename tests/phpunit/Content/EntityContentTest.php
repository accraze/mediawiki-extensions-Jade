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

use Jade\Content\EntityContent;
use Jade\ProposalValidator;
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
 * @coversDefaultClass \Jade\Content\EntityContent
 */
class EntityContentTest extends MediaWikiLangTestCase {

	public function setUp() : void {
		parent::setUp();

		// Mock validation.
		$this->mockValidation = $this->getMockBuilder( ProposalValidator::class )
			->disableOriginalConstructor()
			->getMock();
		$this->setService( 'JadeProposalValidator', $this->mockValidation );

		$this->judgmentText = TestStorageHelper::getJudgmentText( 'diff' );
	}

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$content = new EntityContent( $this->judgmentText );

		$this->assertEquals(
			EntityContent::CONTENT_MODEL_ENTITY,
			$content->getModel() );
	}

	/**
	 * @covers ::prepareSave
	 */
	public function testPrepareSave_success() {
		$content = new EntityContent( $this->judgmentText );
		$page = $this->getExistingTestPage();
		$user = $this->getTestUser()->getUser();

		$this->mockValidation
			->expects( $this->once() )
			->method( 'validateProposalContent' )
			->willReturn( Status::newGood() );
		// $this->mockValidation
		// ->expects( $this->once() )
		// ->method( 'validatePageTitle' )
		// ->willReturn( Status::newGood() );

		$status = $content->prepareSave( $page, 0, 0, $user );
		$this->assertTrue( $status->isOK() );
	}

	/**
	 * @covers ::prepareSave
	 */
	public function testPrepareSave_badContent() {
		$content = new EntityContent( $this->judgmentText );
		$page = $this->getExistingTestPage();
		$user = $this->getTestUser()->getUser();

		$this->mockValidation
			->expects( $this->once() )
			->method( 'validateProposalContent' )
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
		$this->markTestSkipped( 'fix' );
		$content = new EntityContent( $this->judgmentText );
		$page = $this->getExistingTestPage();
		$user = $this->getTestUser()->getUser();

		$this->mockValidation
			->expects( $this->once() )
			->method( 'validateProposalContent' )
			->willReturn( Status::newGood() );
		// $this->mockValidation
		// ->expects( $this->once() )
		// ->method( 'validatePageTitle' )
		// ->willReturn( Status::newFatal( 'jade-bad-entity-type', 'abc' ) );

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
		$content = new EntityContent( $this->judgmentText );

		$this->mockValidation
			->expects( $this->once() )
			->method( 'validateProposalContent' )
			->willReturn( Status::newGood() );

		$this->assertTrue( $content->isValid() );
	}

	public function provideValidationScenarios() {
		yield [
			file_get_contents( __DIR__ . '/../../data/invalid_judgment_bad_json.notjson' ),
			Status::newFatal( 'jade-bad-content-generic' ),
		];
		yield [
			'',
			Status::newFatal( 'jade-bad-content-generic' ),
		];
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
		$content = new EntityContent( $judgmentText );

		if ( $injectStatus !== null ) {
			$this->mockValidation
				->expects( $this->once() )
				->method( 'validateProposalContent' )
				->willReturn( $injectStatus );
		}

		$status = $content->validateContent();

		$this->assertEquals( $expectedStatus, $status );
	}

	/**
	 * @covers ::isEmpty
	 */
	public function testIsEmpty_empty() {
		$content = new EntityContent( '{}' );

		$this->assertTrue( $content->isEmpty() );
	}

	/**
	 * @covers ::isEmpty
	 */
	public function testIsEmpty_notEmpty() {
		$content = new EntityContent( $this->judgmentText );

		$this->assertFalse( $content->isEmpty() );
	}

	/**
	 * @covers ::fillParserOutput
	 */
	public function testFillParserOutput_invalid() {
		$content = new EntityContent( 'FOO' );
		$output = new ParserOutput;

		$content->fillParserOutput(
			Title::newFromDBkey( 'Jade:Diff/123' ),
			123,
			ParserOptions::newFromUser( $this->getTestUser()->getUser() ),
			true,
			$output
		);

		$this->assertSame( '', $output->getRawText() );
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
		$this->markTestSkipped( 'fix' );
		$content = new EntityContent( $this->judgmentText );
		$output = new ParserOutput;

		$this->mockValidation
			->method( 'validateProposalContent' )
			->willReturn( Status::newGood() );
		$this->mockValidation
			->method( 'validatePageTitle' )
			->willReturn( Status::newGood() );
		$page = Title::newFromDBkey( '123' );
		$id = $page->getId();
		$content->fillParserOutput(
			Title::newFromDBkey( 'Jade:Diff/' . $id ),
			$id,
			ParserOptions::newFromUser( $this->getTestUser()->getUser() ),
			$doGenerateHtml,
			$output
		);

		$strippedHtml = Sanitizer::stripAllTags( $output->getText() );
		$this->assertRegExp( $expectedPattern, $strippedHtml );
	}

	/**
	 * @covers ::fillParserOutput
	 * @dataProvider provideFillParserOutput
	 */
	public function testFillParserOutput2( $doGenerateHtml, $expectedPattern ) {
		$content = new EntityContent( $this->judgmentText );
		$output = new ParserOutput;

		$this->mockValidation
			->method( 'validateProposalContent' )
			->willReturn( Status::newGood() );
		$this->mockValidation
			->method( 'validatePageTitle' )
			->willReturn( Status::newGood() );
		$id = '1337';
		$content->fillParserOutput(
			Title::newFromDBkey( 'Jade:Diff/' . $id ),
			$id,
			ParserOptions::newFromUser( $this->getTestUser()->getUser() ),
			true,
			$output
		);

		$strippedHtml = Sanitizer::stripAllTags( $output->getText() );
		$this->assertSame( '', $strippedHtml );
	}

}
