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

use ApiTestCase;
use FormatJSON;

/**
 * Integration tests for the Jade API.
 *
 * @group API
 * @group Database
 * @group medium
 * @group Jade
 *
 * @covers Jade\ApiGetJudgments
 */
class ApiGetJudgmentsTest extends ApiTestCase {

	public function setUp() : void {
		parent::setUp();

		$this->tablesUsed = [
			'page',
			'jade_diff_judgment',
			'jade_revision_judgment',
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

	public function testGetJudgments_invalidEntityType() {
		$this->setExpectedApiException(
			[ 'apierror-unrecognizedvalue', 'gjentitytype', 'foo' ] );
		$result = $this->doApiRequest( [
			'action' => 'query',
			'list' => 'getjudgments',
			'gjentitytype' => 'foo',
			'gjentityid' => 123,
		] );
	}

	public function testGetJudgments_success() {
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$existingJudgment = [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
			] ],
		];
		$status = TestStorageHelper::saveJudgment(
			"Diff/{$revision->getId()}",
			$existingJudgment
		);
		$this->assertTrue( $status->isOK() );

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
					'goodfaith' => true,
				],
				'preferred' => true,
			] ],
		];
		$status = TestStorageHelper::saveJudgment(
			"Diff/{$revision->getId()}",
			$existingJudgment
		);
		$this->assertTrue( $status->isOK() );

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
