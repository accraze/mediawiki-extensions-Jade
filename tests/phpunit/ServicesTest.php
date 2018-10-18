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

use JADE\EntityJudgmentSetStorage;
use JADE\JudgmentIndexStorage;
use JADE\JudgmentValidator;
use JADE\JADEServices;
use JADE\ServiceWiring;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;

/**
 * @group JADE
 */
class ServicesTest extends MediaWikiTestCase {

	public function provideServices() {
		yield [
			'getEntityJudgmentSetStorage',
			'JADEEntityJudgmentSetStorage',
			EntityJudgmentSetStorage::class
		];
		yield [
			'getJudgmentIndexStorage',
			'JADEJudgmentIndexStorage',
			JudgmentIndexStorage::class
		];
		yield [
			'getJudgmentValidator',
			'JADEJudgmentValidator',
			JudgmentValidator::class
		];
	}

	/**
	 * @dataProvider provideServices
	 * @covers JADE\JADEServices
	 */
	public function testJADEServices( $funcName, $_serviceKey, $className ) {
		$service = call_user_func( [ JADEServices::class, $funcName ] );
		$this->assertInstanceOf( $className, $service );
	}

	/**
	 * @dataProvider provideServices
	 * @covers JADE\ServiceWiring::getWiring
	 */
	public function testServiceWiring( $_funcName, $serviceKey, $className ) {
		$wiring = ServiceWiring::getWiring();
		$service = $wiring[$serviceKey]( MediaWikiServices::getInstance() );

		$this->assertInstanceOf( $className, $service );
	}

}
