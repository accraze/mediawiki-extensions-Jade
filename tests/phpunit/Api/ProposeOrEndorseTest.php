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
use Jade\Tests\TestStorageHelper;

/**
 * Integration tests for the Jade API.
 *
 * @group API
 * @group Database
 * @group medium
 * @group Jade
 *
 * @coversDefaultClass \Jade\Api\ProposeOrEndorse
 */
class ProposeOrEndorseTest extends ApiTestCase {

	public function setUp() : void {
		parent::setUp();
	}

	/**
	 * @covers ::execute
	 */
	public function testProposeOrEndorse_createEntityAndEndorseProposalsucess() {
		// Case 1:
		// The entity page doesn't exist at all.
		// Create the page, create the relevant proposal
		// Add an endorsement from the user
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$title = "Diff/{$revision->getId()}";
		$labeldata = '{"damaging": false, "goodfaith": true}';
		$result = $this->doApiRequestWithToken( [
			'action' => 'jadeproposeorendorse',
			'title' => $title,
			'facet' => 'editquality',
			'labeldata' => $labeldata,
			'endorsementorigin' => 'mwapi test',
			'notes' => 'jade-createandendorseproposal',
			'comment' => 'proposeOrEndorse test'
		] );
		$this->assertNotEmpty( $result[0]['data'] );
		$entity = array_shift( $result[0]['data'] );
		$this->assertNotEmpty( $entity['editquality'] );
		$proposals = array_shift( $entity['editquality'] );
		$this->assertSame( $proposals[0]['notes'], 'jade-createandendorseproposal' );
	}

	/**
	 * @covers ::execute
	 */
	public function testProposeOrEndorse_createAndEndorseNewProposalsucess() {
		// Case 2:
		// The entity page exists but no proposal with matching labeldata
		// and the user has not already endorsed a proposal for this facet
		// Then create the relevant proposal.
		// and add an endorsement from the user.
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
						'preferred' => false,
						'author' => [
								'ip' => '10.0.2.2'
						],
						'endorsements' => [
								[
										"author" => [
												"ip" => "10.0.2.2"
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

		$labeldata = '{"damaging": true, "goodfaith": false}';
		$notes = 'new  proposal';
		$result = $this->doApiRequestWithToken( [
			'action' => 'jadeproposeorendorse',
			'title' => $title,
			'facet' => 'editquality',
			'labeldata' => $labeldata,
			'endorsementorigin' => 'mwapi test',
			'notes' => $notes,
			'comment' => 'proposeOrEndorse test'
		] );
		$this->assertNotEmpty( $result[0]['data'] );
		$entity = array_shift( $result[0]['data'] );
		$this->assertNotEmpty( $entity['editquality'] );
		$proposals = array_shift( $entity['editquality'] );
		$this->assertSame( count( $proposals ), 2 );
		$this->assertSame( $proposals[1]['notes'], $notes );
		$this->assertTrue( $proposals[1]['labeldata']['damaging'] );
		$this->assertFalse( $proposals[1]['labeldata']['goodfaith'] );
	}

	/**
	 * @covers ::execute
	 */
	public function testProposeOrEndorse_endorseExistingProposalsucess() {
		// Case 3:
		// The entity page exists and there's a proposal with matching labeldata.
		// (comment: /* jade-endorseproposal */)
		// Add an endorsement from the user.
		// Raise an existingproposalnotesnotoverwritten warning
		// If the proposal is not preferred: Raise a endorsingnonpreferredproposal
		// warning and leave the //preferred bit alone
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
						'preferred' => false,
						'author' => [
							'id' => 2,
							'cid' => 0
						],
						'endorsements' => [
								[
										"author" => [
											"id" => 2,
											"cid" => 0
										],
										"comment" => "As proposer",
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
		$notes = 'jade endorse';
		$comment = 'endorsing this proposal!';
		$result = $this->doApiRequestWithToken( [
			'action' => 'jadeproposeorendorse',
			'title' => $title,
			'facet' => 'editquality',
			'labeldata' => $labeldata,
			'endorsementorigin' => 'mwapi test',
			'endorsementcomment' => $comment,
			'notes' => $notes,
			'comment' => 'proposeOrEndorse test'
		] );
		$this->assertNotEmpty( $result[0]['data'] );
		$entity = array_shift( $result[0]['data'] );
		$this->assertNotEmpty( $entity['editquality'] );
		$proposals = array_shift( $entity['editquality'] );
		$this->assertSame( count( $proposals ), 1 );
		$endorsements = $proposals[0]['endorsements'];
		$this->assertSame( count( $endorsements ), 2 );
		$this->assertSame( $endorsements[1]['comment'], $comment );
	}

	/**
	 * @covers ::execute
	 */
	public function testProposeOrEndorse_moveEndorsesucess() {
		// Case 4:
		// The user already has an endorsement for this facet but it is for
		// a different proposal. A. If the target labeldata is not represented in
		// any proposal: Create the proposal (comment: /*
		// jade-createproposalandmoveendorsement */)
		// Move the user's endorsement to the target proposal and update the
		// endorsementcomment.
		// If the proposal is not preferred: Raise a endorsingnonpreferredproposal
		// warning and leave the preferred bit alone B. Otherwise, if the target
		// labeldata exists, just move the endorsement. (comment: /*
		// jade-moveendorsement */)
		// Raise an existingproposalnotesnotoverwritten warning
		// If the proposal is not preferred: Raise a endorsingnonpreferredproposal
		// warning and leave the preferred bit alone
		//
		list( $page, $revision ) = TestStorageHelper::createEntity();
		$title = "Diff/{$revision->getId()}";
		$existingEntity = [
			'facets' => [
				'editquality' => [
					'proposals' => [ [
						'labeldata' => [
							'damaging' => false,
							'goodfaith' => true,
						],
						'notes' => "i approve of this",
						'preferred' => false,
						'author' => [
								'ip' => '10.0.2.2'
						],
						'endorsements' => [
								[
										"author" => [
												"id" => 1,
												'cid' => 0
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
								'ip' => '10.0.2.2'
						],
						'endorsements' => [
								[
										"author" => [
												"id" => 20,
												'cid' => 0
										],
										"comment" => "As proposer",
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
		$status = TestStorageHelper::saveJudgment(
			$title,
			$existingEntity
		);
		$this->assertTrue( $status->isOK() );

		$labeldata = '{"damaging": true, "goodfaith": false}';
		$notes = 'jade-endorse';
		$comment = 'endorsing this proposal!';
		$result = $this->doApiRequestWithToken( [
			'action' => 'jadeproposeorendorse',
			'title' => $title,
			'facet' => 'editquality',
			'labeldata' => $labeldata,
			'endorsementorigin' => 'mwapi test',
			'endorsementcomment' => $comment,
			'notes' => $notes,
			'comment' => 'proposeOrEndorse test'
		] );
		$this->assertNotEmpty( $result[0]['data'] );
		$entity = array_shift( $result[0]['data'] );
		$this->assertNotEmpty( $entity['editquality'] );
		$proposals = array_shift( $entity['editquality'] );
		$this->assertSame( count( $proposals ), 2 );
		$endorsements = $proposals[1]['endorsements'];
		$this->assertSame( count( $endorsements ), 2 );
		$this->assertSame( $endorsements[1]['comment'], $comment );
	}
}
