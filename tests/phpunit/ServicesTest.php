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

use Jade\EntityJudgmentSetStorage;
use Jade\JudgmentIndexStorage;
use Jade\JudgmentValidator;
use Jade\JadeServices;
use Jade\ServiceWiring;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;

/**
 * @group Jade
 */
class ServicesTest extends MediaWikiTestCase {

	public function provideServices() {
		yield [
			'getEntityJudgmentSetStorage',
			'JadeEntityJudgmentSetStorage',
			EntityJudgmentSetStorage::class
		];
		yield [
			'getJudgmentIndexStorage',
			'JadeJudgmentIndexStorage',
			JudgmentIndexStorage::class
		];
		yield [
			'getJudgmentValidator',
			'JadeJudgmentValidator',
			JudgmentValidator::class
		];
	}

	/**
	 * @dataProvider provideServices
	 * @covers Jade\JadeServices
	 */
	public function testJadeServices( $funcName, $_serviceKey, $className ) {
		$service = call_user_func( [ JadeServices::class, $funcName ] );
		$this->assertInstanceOf( $className, $service );
	}

	/**
	 * @dataProvider provideServices
	 * @covers Jade\ServiceWiring::getWiring
	 */
	public function testServiceWiring( $_funcName, $serviceKey, $className ) {
		$wiring = ServiceWiring::getWiring();
		$service = $wiring[$serviceKey]( MediaWikiServices::getInstance() );

		$this->assertInstanceOf( $className, $service );
	}

}
