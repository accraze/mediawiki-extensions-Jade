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
namespace Jade\Tests\Api;

use ApiTestCase;
use ApiUsageException;
use Jade\Tests\TestStorageHelper;

/**
 * Integration tests for the Jade API.
 *
 * @group API
 * @group Database
 * @group medium
 * @group Jade
 *
 * @coversDefaultClass \Jade\Api\DeleteProposal
 */
class DeleteProposalTest extends ApiTestCase {

	public function setUp() : void {
		parent::setUp();
	}

	/**
	 * @covers ::execute
	 */
	public function testDeleteProposalSucess() {
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$existingEntity = [
			'facets' => [
				'editquality' => [
					'proposals' => [ [
						'labeldata' => [
							'damaging' => false,
							'goodfaith' => true,
						],
						'notes' => "i approve of this",
						'preferred' => true,
						'author' => [
								'ip' => '10.0.2.2'
						],
						'endorsements' => [
								[
										"author" => [
												"ip" => "10.0.2.1"
										],
										"comment" => "another-new-update",
										"origin" => "mwapi",
										"created" => "2019-09-26T03:20:35+00:00",
										"touched" => "2019-09-26T03:21:25+00:00"
								]
						]
					] ]
				],
			 ],
		];
	$title = "Diff/{$revision->getId()}";

		$status = TestStorageHelper::saveJudgment(
			$title,
			$existingEntity
		);
		$this->assertTrue( $status->isOK() );

		$labeldata = '{"damaging": false, "goodfaith": true}';
		$result = $this->doApiRequestWithToken( [
			'action' => 'jadedeleteproposal',
			'title' => $title,
			'facet' => 'editquality',
			'labeldata' => $labeldata,
			'comment' => 'delete proposal test',
		] );
		$this->assertNotEmpty( $result[0]['data'] );
		$entity = array_shift( $result[0]['data'] );
		$this->assertNotEmpty( $entity['editquality'] );
		$proposals = array_shift( $entity['editquality'] );
		$this->assertEmpty( $proposals );
	}

	/**
	 * @covers ::execute
	 */
	public function testDeleteProposalSucessPreferred() {
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$existingEntity = [
			'facets' => [
				'editquality' => [
					'proposals' => [ [
						'labeldata' => [
							'damaging' => false,
							'goodfaith' => true,
						],
						'notes' => "i approve of this",
						'preferred' => true,
						'author' => [
								'ip' => '10.0.2.2'
						],
						'endorsements' => [
								[
										"author" => [
												"ip" => "10.0.2.1"
										],
										"comment" => "another-new-update",
										"origin" => "mwapi",
										"created" => "2019-09-26T03:20:35+00:00",
										"touched" => "2019-09-26T03:21:25+00:00"
								]
						]
					],
					[
						'labeldata' => [
							'damaging' => true,
							'goodfaith' => false,
						],
						'notes' => "i approve of this",
						'preferred' => false,
						'author' => [
							'id' => 2,
							'cid' => 2
						],
						'endorsements' => [
								[
										"author" => [
												'id' => 2,
												'cid' => 2
										],
										"comment" => "another-new-update",
										"origin" => "mwapi",
										"created" => "2019-09-26T03:20:35+00:00",
										"touched" => "2019-09-26T03:21:25+00:00"
								]
						]
					]
				]
				],
			 ],
		];
		$title = "Diff/{$revision->getId()}";

		$status = TestStorageHelper::saveJudgment(
			$title,
			$existingEntity
		);
		$this->assertTrue( $status->isOK() );

		$labeldata = '{"damaging": false, "goodfaith": true}';
		$this->expectException( ApiUsageException::class );
		$result = $this->doApiRequestWithToken( [
			'action' => 'jadedeleteproposal',
			'title' => $title,
			'facet' => 'editquality',
			'labeldata' => $labeldata,
			'comment' => 'delete proposal test',
		] );
	}

}
