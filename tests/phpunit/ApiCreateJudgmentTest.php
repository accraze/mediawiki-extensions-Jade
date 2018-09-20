<?php
namespace JADE\Tests;

use ApiTestCase;
use ApiUsageException;
use FormatJSON;
use RequestContext;
use Title;
use WikiPage;

/**
 * Integration tests for the JADE API.
 *
 * @group API
 * @group Database
 * @group medium
 * @group JADE
 *
 * @covers JADE\ApiCreateJudgment::execute
 * @covers JADE\ApiCreateJudgment::coerceParams
 * @covers JADE\JudgmentAppendCreator
 * @covers JADE\TitleHelper
 */
class ApiCreateJudgmentTest extends ApiTestCase {

	public function setUp() {
		parent::setUp();

		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'recentchanges';
		$this->tablesUsed[] = 'revision';
	}

	public function testCreateJudgment_whenEmpty() {
		list( $page, $revision ) = TestStorageHelper::createEntity();

		$result = $this->doApiRequestWithToken( [
			'action' => 'createjudgment',
			'entitytype' => 'diff',
			'entityid' => $revision->getId(),
			'schema' => 'damaging',
			'data' => 'true',
			'notes' => 'abc',
			'summary' => 'foo',
		] );
		$this->assertSame( 'Success', $result[0]['createjudgment']['status'] );
		$contents = $this->loadJadePage( "Diff/{$revision->getId()}" );
		$expected = [
			'schemas' => [
				'damaging' => [ [
					'data' => true,
					'notes' => 'abc',
				] ]
			],
		];
		$this->assertSame( $expected, $contents );
	}

	public function testCreateJudgment_minimal() {
		list( $page, $revision ) = TestStorageHelper::createEntity();

		$result = $this->doApiRequestWithToken( [
			'action' => 'createjudgment',
			'entitytype' => 'diff',
			'entityid' => $revision->getId(),
			'schema' => 'damaging',
			'data' => 'true',
		] );
		$this->assertSame( 'Success', $result[0]['createjudgment']['status'] );
		$contents = $this->loadJadePage( "Diff/{$revision->getId()}" );
		$expected = [
			'schemas' => [
				'damaging' => [ [
					'data' => true,
				] ]
			],
		];
		$this->assertSame( $expected, $contents );
	}

	public function testCreateJudgment_coerceIntToBool() {
		list( $page, $revision ) = TestStorageHelper::createEntity();

		$result = $this->doApiRequestWithToken( [
			'action' => 'createjudgment',
			'entitytype' => 'diff',
			'entityid' => $revision->getId(),
			'schema' => 'damaging',
			'data' => '0',
		] );
		$this->assertSame( 'Success', $result[0]['createjudgment']['status'] );
		$contents = $this->loadJadePage( "Diff/{$revision->getId()}" );
		$expected = [
			'schemas' => [
				'damaging' => [ [
					'data' => false,
				] ]
			],
		];
		$this->assertSame( $expected, $contents );
	}

	public function testCreateJudgment_appendSameSchema() {
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$existingJudgment = [
			'schemas' => [
				'damaging' => [ [
					'data' => false,
				] ],
			],
		];
		TestStorageHelper::saveJudgment(
			"Diff/{$revision->getId()}",
			$existingJudgment
		);

		$result = $this->doApiRequestWithToken( [
			'action' => 'createjudgment',
			'entitytype' => 'diff',
			'entityid' => $revision->getId(),
			'schema' => 'damaging',
			'data' => 'true',
		] );
		$this->assertSame( 'Success', $result[0]['createjudgment']['status'] );
		$contents = $this->loadJadePage( "Diff/{$revision->getId()}" );
		$expected = [
			'schemas' => [
				'damaging' => [
					[ 'data' => false ],
					[ 'data' => true ],
				]
			],
		];
		$this->assertSame( $expected, $contents );
	}

	public function testCreateJudgment_appendOtherSchema() {
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$existingJudgment = [
			'schemas' => [
				'damaging' => [ [
					'data' => false,
				] ],
			],
		];
		TestStorageHelper::saveJudgment(
			"Diff/{$revision->getId()}",
			$existingJudgment
		);

		$result = $this->doApiRequestWithToken( [
			'action' => 'createjudgment',
			'entitytype' => 'diff',
			'entityid' => $revision->getId(),
			'schema' => 'goodfaith',
			'data' => 'true',
		] );
		$this->assertSame( 'Success', $result[0]['createjudgment']['status'] );
		$contents = $this->loadJadePage( "Diff/{$revision->getId()}" );
		$expected = [
			'schemas' => [
				'damaging' => [
					[ 'data' => false ],
				],
				'goodfaith' => [
					[ 'data' => true ],
				],
			],
		];
		$this->assertSame( $expected, $contents );
	}

	/**
	 * @expectedException ApiUsageException
	 */
	public function testCreateJudgment_protectedNamespace() {
		$this->setMwGlobals( [
			// Parodoxical, nonexistent permission prevents editing.
			'wgNamespaceProtection' => [
				NS_JUDGMENT => 'everyone'
			]
		] );
		RequestContext::resetMain();
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$status = $this->doApiRequestWithToken( [
			'action' => 'createjudgment',
			'entitytype' => 'diff',
			'entityid' => $revision->getId(),
			'schema' => 'goodfaith',
			'data' => 'true',
		] );
	}

	public function testCreateJudgment_translation() {
		$this->setMwGlobals( [
			'wgJadeEntityTypeNames' => [
				'diff' => 'Версия',
			],
		] );
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$status = $this->doApiRequestWithToken( [
			'action' => 'createjudgment',
			'entitytype' => 'diff',
			'entityid' => $revision->getId(),
			'schema' => 'damaging',
			'data' => 'true',
		] );

		$contents = $this->loadJadePage( "Версия/{$revision->getId()}" );
		$expected = [
			'schemas' => [
				'damaging' => [
					[ 'data' => true ],
				],
			],
		];
		$this->assertSame( $expected, $contents );
	}

	public function loadJadePage( $titleText ) {
		$title = Title::makeTitle( NS_JUDGMENT, $titleText );
		$page = WikiPage::factory( $title );
		$content = $page->getContent();
		if ( $content === null ) {
			return null;
		} else {
			return FormatJSON::decode( $content->getNativeData(), true );
		}
	}

}
