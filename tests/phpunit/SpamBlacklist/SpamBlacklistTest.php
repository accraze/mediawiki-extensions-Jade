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
namespace JADE\Tests\SpamBlacklist;

use ApiTestCase;
use BaseBlacklist;
use ExtensionRegistry;
use JADE\Tests\TestStorageHelper;
use ReflectionClass;

/**
 * Check that SpamBlacklist integration works in judgment JSON.
 *
 * @group JADE
 * @group Database
 * @group medium
 * @group SpamBlacklist
 *
 * @covers SpamBlacklist
 */
class SpamBlacklistTest extends ApiTestCase {

	const BLACKLIST_FILE = 'spam_blacklist.txt';

	public function setUp() {
		parent::setUp();

		if ( !ExtensionRegistry::getInstance()->isLoaded( 'SpamBlacklist' ) ) {
			$this->markTestSkipped( 'Can only run test with SpamBlacklist enabled' );
		}

		$this->tablesUsed = [
			'page',
		];

		$blacklistPath = __DIR__ . '/../../data/' . self::BLACKLIST_FILE;
		$this->setMwGlobals(
			'wgBlacklistSettings',
			[ 'spam' => [
				'files' => [ $blacklistPath ],
			] ]
		);

		// Reset the blacklist, which will have already been initialized due to
		// a hook invoked during setUp.
		$reflClass = new ReflectionClass( BaseBlacklist::class );
		$reflProperty = $reflClass->getProperty( 'instances' );
		$reflProperty->setAccessible( true );
		$reflProperty->setValue( [] );
	}

	public function testFilterJudgment_matching() {
		list( $page, $revision ) = TestStorageHelper::createEntity();

		$content = json_encode( [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
				'notes' => 'Visit http://unusual-stringy.tv/',
			] ],
		] );

		$result = $this->doApiRequestWithToken( [
			'action' => 'edit',
			'title' => "Judgment:Diff/{$revision->getId()}",
			'text' => $content,
			'summary' => 'a summary',
		] );

		$expected = [
			'spamblacklist' => 'unusual-stringy',
			'result' => 'Failure',
		];
		$this->assertEquals( $expected, $result[0]['edit'] );
	}

	public function testFilterJudgment_nonmatching() {
		list( $page, $revision ) = TestStorageHelper::createEntity();

		$content = json_encode( [
			'judgments' => [ [
				'schema' => [
					'damaging' => false,
					'goodfaith' => true,
				],
				'preferred' => true,
				'notes' => 'Visit http://casino.gov/harmless-real-casino',
			] ],
		] );

		$result = $this->doApiRequestWithToken( [
			'action' => 'edit',
			'title' => "Judgment:Diff/{$revision->getId()}",
			'text' => $content,
			'summary' => 'a summary',
		] );

		$this->assertEquals( 'Success', $result[0]['edit']['result'] );
	}

}
