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

use InvalidArgumentException;
use JADE\JudgmentTarget;
use MediaWikiTestCase;

/**
 * @group JADE
 *
 * @coversDefaultClass JADE\JudgmentTarget
 */
class JudgmentTargetTest extends MediaWikiTestCase {

	public function provideTargets() {
		yield [ 'diff', 123, true ];
		yield [ 'diff', '123', true ];
		yield [ 'foo', 123, false ];
	}

	/**
	 * @dataProvider provideTargets
	 *
	 * @covers ::newGeneric
	 */
	public function testNewGeneric( $entityType, $entityId, $expectedSuccess ) {
		if ( !$expectedSuccess ) {
			$this->setExpectedException( InvalidArgumentException::class );
		}

		$target = JudgmentTarget::newGeneric( $entityType, $entityId );
		// Trick question: we can only reach this if successful.
		$this->assertTrue( $expectedSuccess );
		$this->assertInstanceOf( JudgmentTarget::class, $target );
	}

}
