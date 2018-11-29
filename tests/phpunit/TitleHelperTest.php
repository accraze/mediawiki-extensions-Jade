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

use Jade\JudgmentEntityType;
use Jade\JudgmentTarget;
use Jade\TitleHelper;
use MediaWikiTestCase;
use TitleValue;

/**
 * @group Jade
 *
 * @coversDefaultClass Jade\TitleHelper
 */
class TitleHelperTest extends MediaWikiTestCase {

	public function setUp() {
		parent::setUp();
		$this->revisionType = JudgmentEntityType::sanitizeEntityType( 'revision' )->value;
	}

	/**
	 * @covers ::buildJadeTitle
	 */
	public function testBuildJadeTitle_success() {
		$target = new JudgmentTarget( $this->revisionType, 123 );
		$title = TitleHelper::buildJadeTitle( $target );
		$this->assertEquals( 'Revision/123', $title->getDBkey() );
	}

	public function provideUnparseableTitles() {
		yield [ NS_TALK, 'Revision/123', 'jade-bad-title-namespace' ];
		yield [ NS_JUDGMENT, 'Revision/123/321', 'jade-bad-title-format' ];
		yield [ NS_JUDGMENT, 'Revision', 'jade-bad-title-format' ];
		yield [ NS_JUDGMENT, 'Foo/123', 'jade-bad-entity-type' ];
		yield [ NS_JUDGMENT, 'Revision/bar', 'jade-bad-entity-id-format' ];
	}

	/**
	 * @dataProvider provideUnparseableTitles
	 * @covers ::parseTitleValue
	 */
	public function testParseTitle_bad( $namespace, $titleStr, $expectedError ) {
		$title = new TitleValue( $namespace, $titleStr );
		$status = TitleHelper::parseTitleValue( $title );
		$this->assertFalse( $status->isOK() );
		$errors = $status->getErrors();
		$this->assertCount( 1, $errors );
		$this->assertEquals( $expectedError, $errors[0]['message'] );
	}

	/**
	 * @covers ::parseTitleValue
	 */
	public function testParseTitle_success() {
		// Provide a localization which won't accidentally match the type
		// identifier.
		$this->setMwGlobals( [
			'wgJadeEntityTypeNames' => [
				'diff' => 'Diffie',
			],
		] );

		$title = new TitleValue( NS_JUDGMENT, 'Diffie/123' );
		$status = TitleHelper::parseTitleValue( $title );
		$this->assertTrue( $status->isOK() );
		$target = $status->value;
		$this->assertEquals( 'diff', $target->entityType );
		$this->assertEquals( 123, $target->entityId );
	}

}
