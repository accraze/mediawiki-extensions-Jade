<?php
namespace JADE\Tests;

use PHPUnit\Framework\TestCase;
use JsonSchema\Validator;

const ROOT_DIR = __DIR__ . '/..';

class TestSchemaValidation extends TestCase {
	public function provideSchemas() {
		return [
			["jsonschema/judgment/v1.json"],
			["jsonschema/scoring/damaging/v1.json"],
		];
	}

	public function provideTestFixtures() {
		return [
			["tests/data/judgment_1.json", "jsonschema/judgment/v1.json"],
		];
	}

	/**
	 * Check that included schemas are valid.
	 *
	 * @dataProvider provideSchemas
	 */
	public function testSchemas( $subject ) {
		$data = json_decode( file_get_contents( ROOT_DIR . '/' . $subject ) );

		$validator = new Validator;
		$validator->validate( $data );

		if ( !$validator->isValid() ) {
			print_r( $validator->getErrors() );
		}
		$this->assertTrue( $validator->isValid() );
	}

	/**
	 * Check that included test fixtures are valid.
	 *
	 * @dataProvider provideTestFixtures
	 */
	public function testFixtures( $subject, $spec ) {
		$data = json_decode( file_get_contents( ROOT_DIR . '/' . $subject ) );
		$schema = (object)['$ref' => 'file://' . realpath( ROOT_DIR . '/' . $spec )];

		$validator = new Validator;
		$validator->validate( $data, $schema );

		if ( !$validator->isValid() ) {
			print_r( $validator->getErrors() );
		}
		$this->assertTrue( $validator->isValid() );
	}
}
