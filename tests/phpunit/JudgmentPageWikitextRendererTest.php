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

use Block;
use CentralIdLookup;
use JADE\JudgmentPageWikitextRenderer;
use LocalIdLookup;
use LogicException;
use MediaWikiLangTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Database
 * @group JADE
 * @group medium
 *
 * @coversDefaultClass JADE\JudgmentPageWikitextRenderer
 *
 * TODO: Could make some tests more unit-y, calling the methods through a
 * TestingAccessWrapper rather than going through the public getWikitext.
 */
class JudgmentPageWikitextRendererTest extends MediaWikiLangTestCase {

	public static function provideBasicSamples() {
		// Renders the damaging schema.
		yield [
			[ 'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
			] ] ],
			'/Not damaging and Good faith/',
		];
		// Notes as unescaped wikitext.
		yield [
			[ 'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'notes' => '[[Main page]]',
				'preferred' => true,
			] ] ],
			'/\[\[Main page\]\]/',
		];
		// IP user.
		yield [
			[ 'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
				'endorsements' => [ [ 'user' => [ 'ip' => '127.0.0.1' ] ] ],
			] ] ],
			'/\[\[User:127.0.0.1\]\]/',
		];
	}

	/**
	 * @dataProvider provideBasicSamples
	 * @covers ::getWikitext
	 * @covers ::getSchemaSummary
	 * @covers ::getUserWikitext
	 */
	public function testBasicRendering( $judgment, $regex ) {
		// Cheesy recursive conversion from array to stdClass.
		$judgmentObject = json_decode( json_encode( $judgment ) );

		$renderer = new JudgmentPageWikitextRenderer;
		$wikitext = $renderer->getWikitext( $judgmentObject );

		$this->assertRegExp( $regex, $wikitext );
	}

	/**
	 * @covers ::getEndorsements
	 * @covers ::getUserWikitext
	 */
	public function testLocalUser() {
		$user = $this->getTestUser()->getUser();

		$judgment = [ 'judgments' => [ [
			'schema' => [
				'damaging' => false,
				'goodfaith' => true,
			],
			'preferred' => true,
			'endorsements' => [ [
				'user' => [
					'id' => $user->getId(),
				]
			] ],
		] ] ];
		$judgmentObject = json_decode( json_encode( $judgment ) );

		$renderer = new JudgmentPageWikitextRenderer;
		$wikitext = $renderer->getWikitext( $judgmentObject );

		$this->assertRegExp( "/\[\[User:{$user->getName()}\]\]/", $wikitext );
	}

	/**
	 * @covers ::getEndorsements
	 * @covers ::getUserWikitext
	 */
	public function testSuppressedUser() {
		$this->tablesUsed[] = 'ipblocks';

		$user = $this->getTestUser()->getUser();

		// Suppress username.
		$block = new Block();
		$block->setTarget( $user->getName() );
		$block->setBlocker( $this->getTestSysop()->getUser() );
		$block->mHideName = true;
		$block->insert();

		$judgment = [ 'judgments' => [ [
			'schema' => [
				'damaging' => false,
				'goodfaith' => true,
			],
			'preferred' => true,
			'endorsements' => [ [
				'user' => [
					'id' => $user->getId(),
				]
			] ],
		] ] ];
		$judgmentObject = json_decode( json_encode( $judgment ) );

		$renderer = new JudgmentPageWikitextRenderer;
		$wikitext = $renderer->getWikitext( $judgmentObject );

		$this->assertRegExp( "/⧼{$user->getId()}⧽/", $wikitext );
	}

	/**
	 * @covers ::getEndorsements
	 * @covers ::getUserWikitext
	 */
	public function testCentralUser() {
		// Disable CentralAuth to simplify bookkeeping.
		$this->setMwGlobals( [
			'wgCentralIdLookupProviders' => [
				'local' => [ 'class' => LocalIdLookup::class ],
			],
		] );

		$user = $this->getTestUser()->getUser();
		$centralUserId = CentralIdLookup::factory()->centralIdFromLocalUser( $user );

		$judgment = [ 'judgments' => [ [
			'schema' => [
				'damaging' => false,
				'goodfaith' => true,
			],
			'preferred' => true,
			'endorsements' => [ [
				'user' => [
					'id' => $user->getId(),
					'cid' => $centralUserId,
				]
			] ],
		] ] ];
		$judgmentObject = json_decode( json_encode( $judgment ) );

		$renderer = new JudgmentPageWikitextRenderer;
		$wikitext = $renderer->getWikitext( $judgmentObject );

		$this->assertRegExp( "/\[\[User:{$user->getName()}\]\]/", $wikitext );
	}

	public function provideSchemaValueMessages() {
		// TODO: Test is sensitive to the English string.  Include messages in
		// the test.
		yield [ 'damaging', true, 'Damaging' ];
		yield [ 'damaging', false, 'Not damaging' ];
		yield [ 'contentquality', 'start', 'Start-class article' ];
		yield [ 'contentquality', 'ABC', 'Content quality "ABC"' ];
		yield [ 'missing', true, '⧼jade-missing-true⧽' ];
	}

	/**
	 * @dataProvider provideSchemaValueMessages
	 * @covers ::getSchemaValueText
	 * @covers ::__construct
	 */
	public function testGetSchemaValueText( $schemaName, $value, $expectedText ) {
		$renderer = TestingAccessWrapper::newFromObject(
			new JudgmentPageWikitextRenderer );
		$text = $renderer->getSchemaValueText( $schemaName, $value );

		$this->assertEquals( $expectedText, $text );
	}

	/**
	 * @covers ::getUserWikitext
	 */
	public function testGetUserWikitext_missingCentralUser() {
		$badCentralId = mt_rand();

		$renderer = TestingAccessWrapper::newFromObject(
			new JudgmentPageWikitextRenderer );
		$userObj = (object)[
			'cid' => $badCentralId,
			'id' => $this->getTestUser()->getUser()->getId(),
		];
		$output = $renderer->getUserWikitext( $userObj );

		$expected = "⧼{$badCentralId}⧽";

		$this->assertEquals( $expected, $output );
	}

	/**
	 * @covers ::getUserWikitext
	 * @expectedException LogicException
	 */
	public function testGetUserWikitext_badStruct() {
		$renderer = TestingAccessWrapper::newFromObject(
			new JudgmentPageWikitextRenderer );
		$userObj = new \stdClass;
		$output = $renderer->getUserWikitext( $userObj );
	}

}
