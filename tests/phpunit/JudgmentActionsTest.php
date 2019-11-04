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

use ApiTestCase;

/**
 * Integration tests for page save validation.
 *
 * @group API
 * @group Database
 * @group Jade
 * @group medium
 *
 * TODO: Rewrite to use API calls.
 * @coversNothing
 */
class JudgmentActionsTest extends ApiTestCase {
	const REV_JUDGMENT_V1 = '../data/valid_revision_judgment.json';
	const REV_JUDGMENT_V2 = '../data/valid_revision_judgment_v2.json';

	public function setUp() : void {
		// Needs to be before setup since this gets cached
	$this->markTestSkipped( 'not in use.' );
		$this->mergeMwGlobalArrayValue(
			'wgGroupPermissions',
			[ 'sysop' => [ 'deleterevision' => true ] ]
		);

		parent::setUp();

		$this->tablesUsed = [
			'page',
			'jade_diff_judgment',
			'jade_revision_judgment',
		];
	}

	public function testCreateRevisionJudgment() {
		// Create target page.
		$article = TestStorageHelper::makeEdit(
			0, 'TestJudgmentActionsPage' . strval( mt_rand() ), 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgment = TestStorageHelper::makeEdit(
			NS_JADE,
			"Revision/{$rev_id}",
			$judgmentText,
			'summary says'
		);
		$this->assertNotNull( $judgment['page'] );
		$this->assertNotNull( $judgment['revision'] );
	}

	public function testUpdateRevisionJudgment() {
		// Create target page.
		$article = TestStorageHelper::makeEdit(
			0, 'TestJudgmentActionsPage' . strval( mt_rand() ), 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create initial revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgment = TestStorageHelper::makeEdit(
			NS_JADE,
			"Revision/{$rev_id}",
			$judgmentText,
			'summary says'
		);
		$this->assertNotNull( $judgment['page'] );
		$this->assertNotNull( $judgment['revision'] );

		// Update the judgment.
		$judgment2Text = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V2 );
		$judgment2 = TestStorageHelper::makeEdit(
			NS_JADE,
			"Revision/{$rev_id}",
			$judgment2Text,
			'summary says'
		);
		$this->assertNotNull( $judgment2['page'] );
		$this->assertNotNull( $judgment2['revision'] );
	}

	public function testSuppressUnsuppressRevisionJudgment() {
		// Create target page.
		$article = TestStorageHelper::makeEdit(
			0, 'TestJudgmentActionsPage' . strval( mt_rand() ), 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create initial revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgment = TestStorageHelper::makeEdit(
			NS_JUDGMENT,
			"Revision/{$rev_id}",
			$judgmentText,
			'summary says'
		);

		// Update the judgment.
		$judgment2Text = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V2 );
		$judgment2 = TestStorageHelper::makeEdit(
			NS_JADE,
			"Revision/{$rev_id}",
			$judgment2Text,
			'summary says'
		);

		// Suppress the first edit.
		$sysop = $this->getTestSysop()->getUser();
		$out = $this->doApiRequest( [
			'action' => 'revisiondelete',
			'type' => 'revision',
			'target' => $judgment['page']->getTitle()->getDbKey(),
			'ids' => $judgment['revision']->getId(),
			'hide' => 'content|user|comment',
			'token' => $sysop->getEditToken(),
		] );
		// Check the output
		$out = $out[0]['revisiondelete'];
		$this->assertEquals( $out['status'], 'Success' );

		// Unsuppress the first edit.
		$out = $this->doApiRequest( [
			'action' => 'revisiondelete',
			'type' => 'revision',
			'target' => $judgment['page']->getTitle()->getDbKey(),
			'ids' => $judgment['revision']->getId(),
			'show' => 'content|user|comment',
			'token' => $sysop->getEditToken(),
		] );
		// Check the output
		$out = $out[0]['revisiondelete'];
		$this->assertEquals( $out['status'], 'Success' );
	}

	public function testCreateJudgment_badTitleRevId() {
		// Create target page.
		$article = TestStorageHelper::makeEdit(
			0,
			'TestJudgmentActionsPage' . strval( mt_rand() ),
			'abcdef',
			'some summary'
		);
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$bad_rev_id = $rev_id + 1;
		$judgment = TestStorageHelper::makeEdit(
			NS_JADE,
			"Revision/{$bad_rev_id}",
			$judgmentText,
			'summary says',
			false
		);
		$this->assertNull( $judgment['page'] );
		$this->assertNull( $judgment['revision'] );
	}

	public function testCreateJudgment_badTitleType() {
		// Create target page.
		$article = TestStorageHelper::makeEdit(
			0, 'TestJudgmentActionsPage' . strval( mt_rand() ), 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgment = TestStorageHelper::makeEdit(
			NS_JADE,
			"Page/{$rev_id}",
			$judgmentText,
			'summary says',
			false
		);
		$this->assertNull( $judgment['page'] );
		$this->assertNull( $judgment['revision'] );
	}

	public function testCreateJudgment_badTitleFormat() {
		// Create target page.
		$article = TestStorageHelper::makeEdit(
			0, 'TestJudgmentActionsPage' . strval( mt_rand() ), 'abcdef', 'some summary' );
		$page_id = $article['page']->getId();
		$rev_id = $article['revision']->getId();

		// Create revision judgment.
		$judgmentText = file_get_contents( __DIR__ . '/' . self::REV_JUDGMENT_V1 );
		$judgment = TestStorageHelper::makeEdit(
			NS_JADE,
			"Page/{$rev_id}/Wrongunder",
			$judgmentText,
			'summary says',
			false
		);
		$this->assertNull( $judgment['page'] );
		$this->assertNull( $judgment['revision'] );
	}

}
