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

use JADE\Content\JudgmentContent;
use JADE\JudgmentSummarizer;
use MediaWikiTestCase;
use TextContent;

/**
 * @group JADE
 *
 * @coversDefaultClass JADE\JudgmentSummarizer
 */
class JudgmentSummarizerTest extends MediaWikiTestCase {

	/**
	 * @covers ::getSummaryFromContent
	 */
	public function testGetSummaryFromContent_success() {
		$content = new JudgmentContent( TestStorageHelper::getJudgmentText( 'diff' ) );
		$status = JudgmentSummarizer::getSummaryFromContent( $content );
		$this->assertTrue( $status->isOK() );
		$this->assertEquals(
			[
				'damaging' => true,
				'goodfaith' => false,
			],
			$status->value
		);
	}

	/**
	 * @covers ::getSummaryFromContent
	 */
	public function testGetSummaryFromContent_failure() {
		$badJson = '[abc';
		$content = new TextContent( $badJson );
		$status = JudgmentSummarizer::getSummaryFromContent( $content );
		$this->assertFalse( $status->isOK() );
	}

}
