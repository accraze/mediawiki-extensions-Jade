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

/**
 * Integration tests for the Jade API.
 *
 * @group API
 * @group Database
 * @group medium
 * @group Jade
 *
 * @coversDefaultClass Jade\Api\CreateAndEndorse
 */
class CreateAndEndorseTest extends ApiTestCase {

	public function setUp() : void {
		parent::setUp();
	}

	/**
	 * @covers ::execute
	 */
	public function testCreateAndEndorseSucess() {
		$labeldata = '{"damaging": true, "goodfaith": false}';
		$result = $this->doApiRequestWithToken( [
			'action' => 'jadecreateandendorse',
			'title' => 'Jade:Diff/1234',
			'facet' => 'editquality',
			'labeldata' => $labeldata,
			'notes' => 'this is a new proposed label.',
			'endorsementorigin' => 'mwapi',
			'comment' => 'this is a test',
		] );
		$this->assertNotEmpty( $result[0]['data'] );
		$entity = array_shift( $result[0]['data'] );
		$this->assertNotEmpty( $entity['editquality'] );
		$proposals = array_shift( $entity['editquality'] );
		$proposaldata = $proposals[0]['labeldata'];
		// print( json_encode( $proposals[0] ) );
		$this->assertSame( json_decode( $labeldata, true ), $proposaldata );
		$this->assertTrue( $proposals[0]['preferred'] );
	}

}
