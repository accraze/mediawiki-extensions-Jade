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

use JADE\JudgmentTarget;
use JADE\TitleHelper;
use MediaWikiTestCase;
use Title;

/**
 * @group JADE
 *
 * @coversDefaultClass JADE\TitleHelper
 */
class TitleHelperTest extends MediaWikiTestCase {

	/**
	 * @covers ::buildJadeTitle
	 */
	public function testBuildJadeTitle_badEntityType() {
		// Make a bad target.
		$target = $this->getMockBuilder( JudgmentTarget::class )
			->disableOriginalConstructor()
			->getMock();

		$target->entityType = 'foo';
		$target->entityId = 123;

		$status = TitleHelper::buildJadeTitle( $target );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertEquals( 1, count( $errors ) );
		$this->assertEquals( 'jade-bad-entity-type', $errors[0]['message'] );
	}

	/**
	 * @covers ::buildJadeTitle
	 */
	public function testBuildJadeTitle_success() {
		$target = JudgmentTarget::newGeneric( 'revision', 123 );
		$status = TitleHelper::buildJadeTitle( $target );
		$this->assertTrue( $status->isOK() );
		$title = $status->value->getDBkey();
		$this->assertEquals( 'Revision/123', $title );
	}

	public function provideUnparseableTitles() {
		yield [ 'Talk:Revision/123', 'jade-bad-title-namespace' ];
		yield [ 'Judgment:Revision/123/321', 'jade-bad-title-format' ];
		yield [ 'Judgment:Revision', 'jade-bad-title-format' ];
		yield [ 'Judgment:Foo/123', 'jade-bad-entity-type' ];
		yield [ 'Judgment:Revision/bar', 'jade-bad-entity-id-format' ];
	}

	/**
	 * @dataProvider provideUnparseableTitles
	 * @covers ::parseTitle
	 */
	public function testParseTitle_bad( $titleStr, $expectedError ) {
		$title = Title::newFromText( $titleStr );
		$status = TitleHelper::parseTitle( $title );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertEquals( 1, count( $errors ) );
		$this->assertEquals( $expectedError, $errors[0]['message'] );
	}

}
