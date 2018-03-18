<?php
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
		yield [ "jsonschema/scoring/damaging/v1.json" ];
		yield [ "jsonschema/scoring/drafttopic/v1.json" ];
		yield [ "jsonschema/scoring/goodfaith/v1.json" ];
		yield [ "jsonschema/scoring/itemquality/v1.json" ];
		yield [ "jsonschema/scoring/wp10/v1.json" ];
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
