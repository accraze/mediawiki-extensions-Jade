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

use PHPUnit\Framework\TestCase;
use JsonSchema\Validator;

const ROOT_DIR = '../..';

/**
 * @group JADE
 * @coversNothing
 */
class SchemaValidationTest extends TestCase {

	public function provideSchemas() {
		yield [ "jsonschema/judgment/v1.json" ];
	}

	/**
	 * Check that included schemas are valid.
	 *
	 * @dataProvider provideSchemas
	 *
	 * @param string $subject Path to schema being validated.
	 */
	public function testSchemas( $subject ) {
		$data = json_decode( file_get_contents( __DIR__ . '/' . ROOT_DIR . '/' . $subject ) );

		$validator = new Validator;
		$validator->validate( $data );

		if ( !$validator->isValid() ) {
			print_r( $validator->getErrors() );
		}
		$this->assertTrue( $validator->isValid() );
	}

}
