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
namespace Jade\Tests;

use CentralIdLookup;
use FormatJson;
use Jade\JadeServices;
use LocalIdLookup;
use MediaWiki\Block\DatabaseBlock;
use MediaWikiTestCase;
use StatusValue;
use User;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Database
 * @group Jade
 * @group medium
 *
 * @coversDefaultClass \Jade\EntityValidator
 * @covers ::__construct
 *
 * TODO: Should construct directly rather than relying on service wiring.
 */
class EntityValidatorTest extends MediaWikiTestCase {

	const DATA_DIR = '../data';

	/** @var User */
	private $user;

	public function setUp() : void {
		parent::setUp();

		$this->tablesUsed = [
			'ipblocks',
			'jade_diff_judgment',
			'jade_revision_judgment',
			'page',
		];

		$this->user = $this->getTestUser()->getUser();

		// Disable CentralAuth provider for CentralIdLookup.
		$this->setMwGlobals( [
			'wgCentralIdLookupProviders' => [
				'local' => [ 'class' => LocalIdLookup::class ],
			],
			'wgCentralIdLookupProvider' => 'local',
		] );
	}

	public function provideInvalidSchemaContent() {
		yield [ 'invalid_judgment_missing_required.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_bad_json.notjson', 'jade-bad-content' ];
		yield [ 'invalid_judgment_bad_score_data.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_bad_score_schema.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_additional_properties.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_additional_properties2.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_additional_properties3.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_empty_endorsements.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_none_preferred.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_two_preferred.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_bad_contentquality_data.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_bad_user_ip.json', 'jade-bad-content' ];
		yield [ 'invalid_judgment_bad_user_ip2.json', 'jade-bad-content' ];
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
	 * @covers ::validateAgainstSchema
	 * @covers ::validateContentQualityScale
	 * @covers ::validateBasicSchema
	 * @covers ::validateEndorsementUsers
	 * @covers ::validateEntityContent
	 * @covers ::validatePreferred
	 */
	public function testInvalidSchemaContent( $path, $expectedError ) {
		$status = $this->runValidation( $path );

		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( $expectedError, $errors[0]['message'] );
	}

	/**
	 * @param string $path JSON file with judgment page content to validate.
	 * @return StatusValue validation success or errors.
	 */
	protected function runValidation( $path ) {
		$text = file_get_contents( __DIR__ . '/' . self::DATA_DIR . '/' . $path );

		$validator = JadeServices::getEntityValidator();
		$data = FormatJson::decode( $text );
		return $validator->validateEntityContent( $data );
	}

	/**
	 * @dataProvider provideValidJudgments
	 *
	 * @param string $path Path to test fixture, relative to the test data
	 * directory.
	 *
	 * @covers ::validateBasicSchema
	 * @covers ::validateEndorsementUsers
	 * @covers ::validateEntityContent
	 * @covers ::validatePreferred
	 */
	public function testValidateJudgmentContent( $path ) {
		$this->markTestSkipped( 'fix' );
		$status = $this->runValidation( $path );
		$this->assertTrue( $status->isOK() );
	}

	/**
	 * @dataProvider provideValidJudgments
	 *
	 * @covers ::validateEntity
	 * @covers ::validateEntitySchema
	 * @covers ::validatePageTitle
	 */
	public function testValidatePageTitle_valid( $path, $type ) {
		$this->markTestSkipped( 'fix' );
		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$ucType = ucfirst( $type );
		$title = "{$ucType}/{$revision->getId()}";
		$text = file_get_contents( __DIR__ . '/' . self::DATA_DIR . '/' . $path );

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
	 * @covers ::validateEntity
	 * @covers ::validateEntitySchema
	 * @covers ::validatePageTitle
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
		$text = file_get_contents( __DIR__ . '/' . self::DATA_DIR . '/' . $path );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-content', $errors[0]['message'] );
	}

	/**
	 * @covers ::validatePageTitle
	 */
	public function testValidatePageTitle_invalidLong() {
		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Revision/{$revision->getId()}/foo";
		$text = file_get_contents(
			__DIR__ . '/' . self::DATA_DIR . '/valid_revision_judgment.json' );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-content', $errors[0]['message'] );
	}

	/**
	 * @covers ::validatePageTitle
	 */
	public function testValidatePageTitle_invalidShort() {
		$title = 'Revision';
		$text = file_get_contents( __DIR__ . '/' . self::DATA_DIR . '/valid_revision_judgment.json' );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-content', $errors[0]['message'] );
	}

	/**
	 * @covers ::validatePageTitle
	 */
	public function testValidatePageTitle_invalidNonCanonicalLeadingZero() {
		$this->markTestSkipped( 'broken' );
		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Revision/0{$revision->getId()}";
		$text = file_get_contents( __DIR__ . '/' . self::DATA_DIR . '/valid_diff_judgment.json' );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-content', $errors[0]['message'] );
		$this->assertEquals( "Revision/{$revision->getId()}", $errors[0]['params'][0] );
	}

	/**
	 * @covers ::validateEntity
	 * @covers ::validatePageTitle
	 */
	public function testValidatePageTitle_invalidRevision() {
		// A revision that will "never" exist.  We don't create an entity for this test.
		$title = 'Revision/999999999';
		$text = file_get_contents( __DIR__ . '/' . self::DATA_DIR . '/valid_diff_judgment.json' );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-content', $errors[0]['message'] );
	}

	/**
	 * @covers ::validateEndorsementUsers
	 */
	public function testValidateEndorsementUsers_goodId() {
		$this->markTestSkipped( 'broken schema' );
		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Diff/{$revision->getId()}";
		$text = json_encode( [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
				'endorsements' => [ [
					'user' => [
						'id' => $this->user->getId(),
					],
					'created' => date( DATE_ATOM ),
				] ],
			] ]
		] );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertTrue( $status->isOK() );
	}

	/**
	 * @covers ::validateEndorsementUsers
	 */
	public function testValidateEndorsementUsers_badId() {
		$userId = mt_rand();
		// But never create the user...

		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Diff/{$revision->getId()}";
		$text = json_encode( [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
				'endorsements' => [ [
					'user' => [
						'id' => $userId,
					],
					'created' => date( DATE_ATOM ),
				] ],
			] ]
		] );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-content', $errors[0]['message'] );
	}

	/**
	 * @covers ::validateEndorsementUsers
	 */
	public function testValidateEndorsementUsers_goodCid() {
		$this->markTestSkipped( 'broken' );
		$centralUserId = CentralIdLookup::factory()->centralIdFromLocalUser( $this->user );

		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Diff/{$revision->getId()}";
		$text = json_encode( [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
				'endorsements' => [ [
					'user' => [
						'id' => $this->user->getId(),
						'cid' => $centralUserId,
					],
					'created' => date( DATE_ATOM ),
				] ],
			] ]
		] );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertTrue( $status->isOK() );
	}

	/**
	 * Should be able to use suppressed users since we're only showing the ID.
	 *
	 * @covers ::validateEndorsementUsers
	 */
	public function testValidateEndorsementUsers_suppressedCid() {
		$this->markTestSkipped( 'broken' );
		$centralUserId = CentralIdLookup::factory()->centralIdFromLocalUser( $this->user );

		// Suppress username.
		$block = new DatabaseBlock( [
			'address' => $this->user->getName(),
			'by' => $this->getTestSysop()->getUser()->getId(),
			'hideName' => true,
		] );
		$block->insert();

		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Diff/{$revision->getId()}";
		$text = json_encode( [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
				'endorsements' => [ [
					'user' => [
						'id' => $this->user->getId(),
						'cid' => $centralUserId,
					],
					'created' => date( DATE_ATOM ),
				] ],
			] ]
		] );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertTrue( $status->isOK() );
	}

	/**
	 * @covers ::validateEndorsementUsers
	 */
	public function testValidateEndorsementUsers_badCid() {
		// Provide a valid local user ID to be sure we're testing the cid.
		$localUserId = $this->user->getId();

		// Make sure the central user ID is wrong.
		$centralUserId = mt_rand();

		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Diff/{$revision->getId()}";
		$text = json_encode( [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
				'endorsements' => [ [
					'user' => [
						'id' => $localUserId,
						'cid' => $centralUserId,
					],
					'created' => date( DATE_ATOM ),
				] ],
			] ]
		] );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-content', $errors[0]['message'] );
	}

	/**
	 * Local and Global IDs are different users.
	 *
	 * @covers ::validateEndorsementUsers
	 */
	public function testValidateEndorsementUsers_idMismatch() {
		// Valid local user ID.
		$localUserId = $this->user->getId();

		// Different central user's ID.
		$user2 = $this->getTestSysop()->getUser();
		$centralUserId = CentralIdLookup::factory()->centralIdFromLocalUser( $user2 );

		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Diff/{$revision->getId()}";
		$text = json_encode( [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
				'endorsements' => [ [
					'user' => [
						'id' => $localUserId,
						'cid' => $centralUserId,
					],
					'created' => date( DATE_ATOM ),
				] ],
			] ]
		] );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-content', $errors[0]['message'] );
	}

	public function provideTimestamps() {
		// Note that we're slightly stricter than ISO 8601.
		yield [ '2001-04-01T22:22:22Z', true ];
		yield [ '2001-04-01T22:22:22+01:30', true ];
		yield [ '2001-04-01', false ];
		yield [ '2001-04-01T22:22:22', false ];
		yield [ '20010401T222222Z', true ];
		yield [ '10-04', false ];
		yield [ '10-foo', false ];
	}

	/**
	 * Timestamp parsing
	 *
	 * @dataProvider provideTimestamps
	 * @covers ::validateEndorsementTimestamps
	 */
	public function testValidateTimestamps( $timestamp, $expectedSuccess ) {
		$this->markTestSkipped( 'broken' );
		list( $page, $revision ) = TestStorageHelper::createEntity( $this->user );
		$title = "Diff/{$revision->getId()}";
		$text = json_encode( [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
				'endorsements' => [ [
					'user' => [
						'id' => $this->user->getId(),
					],
					'created' => $timestamp,
				] ],
			] ]
		] );

		$status = TestStorageHelper::saveJudgment( $title, $text, $this->user );
		if ( $expectedSuccess ) {
			$this->assertTrue( $status->isOK() );
		} else {
			$this->assertFalse( $status->isOK() );
			$errors = $status->getErrors();
			$this->assertCount( 1, $errors );
			$this->assertEquals( 'jade-created-timestamp-invalid', $errors[0]['message'] );
		}
	}

	/**
	 * @covers ::validateEntity
	 */
	public function testValidateEntity_badType() {
		$validator = JadeServices::getEntityValidator();

		$validator = TestingAccessWrapper::newFromObject( $validator );
		$status = $validator->validateEntity( 'foo', 123 );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-bad-entity-type', $errors[0]['message'] );
		$this->assertEquals( 'foo', $errors[0]['params'][0] );
	}

}
