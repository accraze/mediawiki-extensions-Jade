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

use FormatJSON;
use Jade\JudgmentEntityType;
use Jade\JadeServices;
use Jade\JudgmentTarget;
use MediaWikiTestCase;
use WikiPage;

/**
 * @group Database
 * @group Jade
 * @group medium
 *
 * @coversDefaultClass Jade\PageEntityJudgmentSetStorage
 */
class PageEntityJudgmentSetStorageTest extends MediaWikiTestCase {

	const JUDGMENT_V1 = 'valid_revision_judgment.json';
	const JUDGMENT_V2 = 'valid_revision_judgment_v2.json';
	const DATA_DIR = '../data';

	private $storage;

	public function setUp() : void {
		$this->storage = JadeServices::getEntityJudgmentSetStorage();
		$this->tablesUsed = [
			'jade_diff_judgment',
			'jade_revision_judgment',
			'page',
		];

		parent::setUp();

		$this->revisionType = JudgmentEntityType::sanitizeEntityType( 'revision' )->value;
	}

	public function tearDown() : void {
		global $wgUser;

		parent::tearDown();

		// wgGroupPermissions are cached in the User object, so be sure to reset.
		$wgUser->clearInstanceCache();
	}

	/**
	 * Return sample judgment as an array.
	 *
	 * @param string $path Fixture file name.
	 * @return array Data for a sample judgment page.
	 */
	private function getJudgment( $path = self::JUDGMENT_V1 ) {
		$text = $this->getJudgmentText( $path );
		return FormatJSON::decode( $text, true );
	}

	/**
	 * Return sample judgment text.
	 * @param string $path Fixture file name.
	 * @return string Text for a sample judgment page.
	 */
	private function getJudgmentText( $path = self::JUDGMENT_V1 ) {
		return file_get_contents( __DIR__ . '/' . self::DATA_DIR . '/' . $path );
	}

	/**
	 * @covers ::storeJudgmentSet
	 */
	public function testStoreJudgmentSet_cannotCreate() {
		global $wgUser;
		// Prevent page creation.
		$this->setMwGlobals( [
			'wgGroupPermissions' => [
				'*' => [ 'create' => false ],
			]
		] );
		// FIXME: Not sure why we have to flush twice here.
		$wgUser->clearInstanceCache();

		// Should return a failure status.
		$status = $this->storage->storeJudgmentSet(
			new JudgmentTarget( $this->revisionType, mt_rand() ),
			$this->getJudgment(),
			'summary',
			[]
		);
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-cannot-create-page', $errors[0]['message'] );
	}

	/**
	 * @covers ::storeJudgmentSet
	 */
	public function testStoreJudgmentSet_cannotEdit() {
		global $wgUser;

		// Prevent editing.
		$this->setMwGlobals( [
			'wgGroupPermissions' => [
				'*' => [ 'edit' => false ],
			]
		] );
		// FIXME: unknown why we have to flush twice.
		$wgUser->clearInstanceCache();

		// Create a normal judgment to be edited.
		list( $entityPage, $entityRevision ) = TestStorageHelper::createEntity();
		TestStorageHelper::saveJudgment(
			"Revision/{$entityRevision->getId()}",
			$this->getJudgmentText() );

		// Should return a failure status.
		$status = $this->storage->storeJudgmentSet(
			new JudgmentTarget( $this->revisionType, $entityRevision->getId() ),
			$this->getJudgment( self::JUDGMENT_V2 ),
			'summary',
			[]
		);
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'jade-cannot-edit-page', $errors[0]['message'] );
	}

	/**
	 * @covers ::storeJudgmentSet
	 */
	public function testStoreJudgmentSet_cannotAddTag() {
		list( $entityPage, $entityRevision ) = TestStorageHelper::createEntity();

		// Prevent adding tags.
		$this->setMwGlobals( [
			'wgGroupPermissions' => [
				'*' => [ 'applychangetags' => false ],
			]
		] );

		// Should return a failure status.
		$status = $this->storage->storeJudgmentSet(
			new JudgmentTarget( $this->revisionType, $entityRevision->getId() ),
			$this->getJudgment(),
			'summary',
			[ 'tag_it' ]
		);
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( 'tags-apply-no-permission', $errors[0]['message'] );
	}

	/**
	 * @covers ::storeJudgmentSet
	 */
	public function testStoreJudgmentSet_success() {
		// Create an entity to be judged.
		list( $entityPage, $entityRevision ) = TestStorageHelper::createEntity();

		// Store the judgment.
		$status = $this->storage->storeJudgmentSet(
			new JudgmentTarget( $this->revisionType, $entityRevision->getId() ),
			$this->getJudgment(),
			'summary',
			[]
		);
		$this->assertTrue( $status->isOK() );

		$title = "Revision/{$entityRevision->getId()}";
		list( $page, $storedJudgment ) = TestStorageHelper::loadJudgment( $title );
		$this->assertEquals( $this->getJudgment(), $storedJudgment );
	}

	/**
	 * @covers ::loadJudgmentSet
	 */
	public function testLoadJudgmentSet_empty() {
		// Create an entity to be judged.
		list( $entityPage, $entityRevision ) = TestStorageHelper::createEntity();

		// Store using test helper to isolate function under test.
		$status = TestStorageHelper::saveJudgment(
			"Revision/{$entityRevision->getId()}",
			$this->getJudgmentText() );
		$this->assertTrue( $status->isOK() );

		// Delete the article so that getContent returns null.
		$judgmentPageId = $status->value['revision']->getPage();
		$judgmentPage = WikiPage::newFromID( $judgmentPageId );
		$success = $judgmentPage->doDeleteArticle( 'reason' );
		$this->assertTrue( $success );

		$target = new JudgmentTarget( $this->revisionType, $entityRevision->getId() );
		$status = $this->storage->loadJudgmentSet( $target );
		$this->assertTrue( $status->isOK() );
		$this->assertEquals( [], $status->value );
	}

	/**
	 * @covers ::loadJudgmentSet
	 */
	public function testLoadJudgmentSet_success() {
		// Create an entity to be judged.
		list( $entityPage, $entityRevision ) = TestStorageHelper::createEntity();

		// Store using test helper to isolate function under test.
		$success = TestStorageHelper::saveJudgment(
			"Revision/{$entityRevision->getId()}",
			$this->getJudgmentText() );
		$this->assertTrue( $success->isOK() );

		$target = new JudgmentTarget( $this->revisionType, $entityRevision->getId() );
		$status = $this->storage->loadJudgmentSet( $target );
		$this->assertTrue( $status->isOK() );
		$this->assertEquals( $this->getJudgment(), $status->value );
	}

}
