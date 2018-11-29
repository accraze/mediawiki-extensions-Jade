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
use MediaWikiLangTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Jade
 *
 * @coversDefaultClass Jade\JudgmentEntityType
 */
class JudgmentEntityTypeTest extends MediaWikiLangTestCase {

	public function provideTypeNames() {
		yield [ 'diff', true ];
		yield [ 'revision', true ];
		yield [ 'Diff', false ];
		yield [ '', false ];
		yield [ 1, false ];
	}

	/**
	 * @covers ::__construct
	 * @covers ::sanitizeEntityType
	 * @dataProvider provideTypeNames
	 */
	public function testSanitizeEntityType( $typeName, $expectedSuccess ) {
		$status = JudgmentEntityType::sanitizeEntityType( $typeName );

		$this->assertEquals( $expectedSuccess, $status->isOK() );
		if ( $expectedSuccess ) {
			$entityType = TestingAccessWrapper::newFromObject( $status->value );
			$this->assertEquals( $typeName, $entityType->entityType );
		}
	}

	/**
	 * @covers ::__toString
	 */
	public function testToString() {
		$status = JudgmentEntityType::sanitizeEntityType( 'diff' );
		$entityType = $status->value;
		$this->assertEquals( 'diff', (string)$entityType );
	}

	/**
	 * @covers ::getLocalizedName
	 */
	public function testGetLocalizedName() {
		$this->setMwGlobals( [
			'wgJadeEntityTypeNames' => [
				'diff' => 'Diffie',
			]
		] );
		$status = JudgmentEntityType::sanitizeEntityType( 'diff' );
		$entityType = $status->value;
		$this->assertEquals( 'Diffie', $entityType->getLocalizedName() );
	}

}
