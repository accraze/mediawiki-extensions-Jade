<?php
namespace JADE\Tests;

use ApiTestCase;
use FormatJSON;

/**
 * Integration tests for the JADE API.
 *
 * @group API
 * @group Database
 * @group medium
 * @group JADE
 *
 * @covers JADE\ApiGetJudgments::run
 */
class ApiGetJudgmentsTest extends ApiTestCase {

	public function setUp() {
		parent::setUp();

		$this->tablesUsed = [
			'page',
			'recentchanges',
			'revision',
		];
	}

	public function testGetJudgments_empty() {
		list( $page, $revision ) = TestStorageHelper::createEntity();

		$result = $this->doApiRequest( [
			'action' => 'query',
			'list' => 'getjudgments',
			'gjentitytype' => 'diff',
			'gjentityid' => $revision->getId(),
		] );
		$this->assertSame( [], $result[0]['query']['getjudgments'] );
	}

	public function testGetJudgments_success() {
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$existingJudgment = [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
				],
			] ],
		];
		$success = TestStorageHelper::saveJudgment(
			"Diff/{$revision->getId()}",
			$existingJudgment
		);
		$this->assertTrue( $success );

		$result = $this->doApiRequest( [
			'action' => 'query',
			'list' => 'getjudgments',
			'gjentitytype' => 'diff',
			'gjentityid' => $revision->getId(),
		] );
		$this->assertSame(
			[ [ 'ns' => NS_JUDGMENT, 'title' => "Judgment:Diff/{$revision->getId()}" ] ],
			$result[0]['query']['getjudgments']
		);
	}

	public function testGetJudgments_generator() {
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$existingJudgment = [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
				],
			] ],
		];
		$success = TestStorageHelper::saveJudgment(
			"Diff/{$revision->getId()}",
			$existingJudgment
		);
		$this->assertTrue( $success );

		$result = $this->doApiRequest( [
			'action' => 'query',
			'generator' => 'getjudgments',
			'ggjentitytype' => 'diff',
			'ggjentityid' => $revision->getId(),
			'prop' => 'revisions',
			'rvprop' => 'content',
			'rvslots' => '*',
		] );
		$this->assertNotEmpty( $result[0]['query']['pages'] );
		$page = array_shift( $result[0]['query']['pages'] );
		$this->assertNotEmpty( $page['revisions'] );
		$revision = array_shift( $page['revisions'] );
		$judgment = FormatJSON::decode( $revision['slots']['main']['content'], true );
		$this->assertSame( $existingJudgment, $judgment );
	}

}
