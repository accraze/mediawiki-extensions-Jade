<?php
namespace JADE\Tests;

use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;

/**
 * @group JADE
 * @group Database
 * @group medium
 *
 * @covers JADE\JudgmentAppendCreator
 */
class JudgmentAppendCreatorTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'recentchanges';
		$this->tablesUsed[] = 'revision';
	}

	/**
	 * Create the first judgment on an entity.
	 */
	public function testCreate_new() {
		$creator = MediaWikiServices::getInstance()->getService( 'JADEAppendCreator' );

		// Create target page.
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$revId = $revision->getId();

		// TODO: Test tags

		$creator->createJudgment(
			'diff',
			$revId,
			'damaging',
			true,
			'some reason or thought',
			'Patrolling for damage',
			[]
		);

		$expected = [ 'schemas' => [ 'damaging' => [
			[ 'data' => true, 'notes' => 'some reason or thought' ],
		] ] ];
		list( $page, $content ) = TestStorageHelper::loadJudgment( "Diff/{$revId}" );
		$this->assertEquals( $expected, $content );
	}

	/**
	 * Create judgment where one already exists.
	 */
	public function testCreate_existing() {
		$creator = MediaWikiServices::getInstance()->getService( 'JADEAppendCreator' );

		// Create target page.
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$revId = $revision->getId();

		$creator->createJudgment( 'diff', $revId, 'damaging', true,
			'some reason or thought', 'Patrolling for damage', []
		);

		// New, opposing judgment.
		$creator->createJudgment( 'diff', $revId, 'damaging', false,
			'Different approach.', 'Patrolling for damage', []
		);

		$expected = [ 'schemas' => [ 'damaging' => [
			[ 'data' => true, 'notes' => 'some reason or thought' ],
			[ 'data' => false, 'notes' => 'Different approach.' ],
		] ] ];
		list( $page, $content ) = TestStorageHelper::loadJudgment( "Diff/{$revId}" );
		$this->assertEquals( $expected, $content );
	}

}
