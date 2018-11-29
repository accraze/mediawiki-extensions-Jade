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
namespace Jade\Tests\Content;

use Jade\Tests\TestStorageHelper;
use MediaWikiLangTestCase;
use Title;
use SearchEngine;
use WikiPage;

/**
 * @group Database
 * @group Jade
 * @group medium
 *
 * @coversDefaultClass Jade\Content\JudgmentContentHandler
 */
class JudgmentContentHandlerTest extends MediaWikiLangTestCase {

	/**
	 * @covers ::getDataForSearchIndex
	 */
	public function testGetDataForSearchIndex() {
		// Store a healthy judgment.
		list( $entityPage, $entityRevision ) = TestStorageHelper::createEntity();
		$judgmentTitle = Title::newFromDBkey( "Judgment:Revision/{$entityRevision->getId()}" );
		$status = TestStorageHelper::saveJudgment(
			$judgmentTitle->getDBkey(),
			TestStorageHelper::getJudgmentText( 'revision' ) );
		$this->assertTrue( $status->isOK() );

		$judgmentPage = WikiPage::newFromId( $status->value['revision']->getPage() );
		$parserOutput = $judgmentPage->getContent()->getParserOutput( $judgmentTitle );
		$mockEngine = $this->createMock( SearchEngine::class );
		$data = $judgmentPage->getContentHandler()->getDataForSearchIndex(
			$judgmentPage, $parserOutput, $mockEngine );

		// Has labels
		$this->assertRegExp( '/C-class article/', $data['text'] );
		// No HTML tags
		$this->assertNotRegExp( '/</', $data['text'] );
	}

}
