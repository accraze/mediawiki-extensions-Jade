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

use JADE\JudgmentEntityType;
use JADE\JudgmentTarget;
use PHPUnit\Framework\TestCase;

/**
 * @group JADE
 *
 * @coversDefaultClass JADE\JudgmentTarget
 */
class JudgmentTargetTest extends TestCase {

	/**
	 * @covers ::__construct
	 */
	public function testConstruct() {
		$entityType = JudgmentEntityType::sanitizeEntityType( 'diff' )->value;
		$entityId = 123;
		$target = new JudgmentTarget( $entityType, $entityId );
		$this->assertInstanceOf( JudgmentTarget::class, $target );
		$this->assertEquals( $entityType, $target->entityType );
		$this->assertEquals( $entityId, $target->entityId );
	}

}
