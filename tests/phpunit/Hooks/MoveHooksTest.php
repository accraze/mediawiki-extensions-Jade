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

use ApiTestCase;
use Jade\Content\EntityContent;
use Jade\Tests\TestStorageHelper;
use Status;
use WikiPage;

/**
 * @group API
 * @group Database
 * @group Jade
 * @group medium
 *
 * @covers Jade\Hooks\MoveHooks
 */
class MoveHooksTest extends ApiTestCase {
	const DIFF_JUDGMENT = '../../data/valid_diff_judgment.json';

	const MAIN_EXISTING = 'main-existing';
	const MAIN_NEW = 'main-new';
	const JUDGMENT_EXISTING = 'judgment-existing';
	const JUDGMENT_NEW = 'judgment-new';

	public function setUp() : void {
		parent::setUp();
		$this->markTestSkipped( ' notin use.' );
		$this->tablesUsed = [
			'page',
			'jade_diff_judgment',
			'jade_revision_judgment',
		];

		// Create target content page.
		$this->article = TestStorageHelper::makeEdit(
			NS_MAIN, 'TestJudgmentActionsPage', 'abcdef', 'some summary' );
		$revisionId = $this->article['revision']->getId();

		// Create diff judgment.
		$judgmentTitle = "Diff/{$revisionId}";
		$judgmentText = file_get_contents( __DIR__ . '/' . self::DIFF_JUDGMENT );
		$status = TestStorageHelper::saveJudgment(
			$judgmentTitle,
			$judgmentText
		);
		$this->assertTrue( $status->isOK() );
		$this->judgmentPage = WikiPage::newFromID( $status->value['revision-record']->getPageId() );
		$this->assertTrue( $this->judgmentPage->exists() );

		// Provide the articles as a map from enum since data providers don't
		// have access to the initialized test case.
		$this->articleMap = [
			self::MAIN_EXISTING => "{$this->article['page']->getTitle()->getDBkey()}",
			self::MAIN_NEW => 'New page' . strval( mt_rand() ),
			self::JUDGMENT_EXISTING => "Jade:{$this->judgmentPage->getTitle()->getDBkey()}",
			self::JUDGMENT_NEW => 'Jade:Diff/321' . strval( mt_rand() ),
		];

		// Disable validation since that would prevent moving a wiki page into
		// the Judgment namespace.
		$this->mockValidation = $this->getMockBuilder( ProposalValidator::class )
			->disableOriginalConstructor()
			->setMethods( [ 'validateProposalContent', 'validatePageTitle' ] )
			->getMock();
		$this->setService( 'JadeProposalValidator', $this->mockValidation );

		$this->mockValidation
			->method( 'validateProposalContent' )
			->willReturn( Status::newGood() );
		$this->mockValidation
			->method( 'validatePageTitle' )
			->willReturn( Status::newGood() );
	}

	public function provideNamespaceCombos() {
		// FIXME: The judgment titles would fail validation, but maybe this is
		// okay since we're testing for a specific error code.
		yield [
			self::MAIN_EXISTING,
			self::MAIN_NEW,
		];
		yield [
			self::MAIN_EXISTING,
			self::JUDGMENT_NEW,
			'jade-invalid-move-any',
		];
		yield [
			self::JUDGMENT_EXISTING,
			self::MAIN_NEW,
			[ 'content-not-allowed-here', EntityContent::CONTENT_MODEL_ENTITY, self::MAIN_NEW, 'main' ],
		];
		yield [
			self::JUDGMENT_EXISTING,
			self::JUDGMENT_NEW,
			'jade-invalid-move-any',
		];
	}

	/**
	 * @covers Jade\Hooks\MoveHooks::onMovePageIsValidMove
	 * @dataProvider provideNamespaceCombos
	 */
	public function testOnMovePageIsValidMove(
		$oldTitleKey,
		$newTitleKey,
		$expectedException = null
	) {
		$oldTitle = $this->articleMap[$oldTitleKey];
		$newTitle = $this->articleMap[$newTitleKey];

		if ( $expectedException !== null ) {
			// FIXME: hack to inject calculated values into the provided message fixture.
			$expectedException = array_map(
				function ( $item ) {
					return $this->articleMap[$item] ?? $item;
				},
				(array)$expectedException
			);
			$this->setExpectedApiException( $expectedException );
		}

		$result = $this->doApiRequestWithToken( [
			'action' => 'move',
			'from' => $oldTitle,
			'to' => $newTitle,
			'ignorewarnings' => true,
		] );

		if ( $expectedException === null ) {
			// FIXME: test !$result[0]['move']['error'] instead, but what does move call the error field?
			$this->assertTrue( $result[0]['move']['redirectcreated'] );
		}
	}

}
