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

use AssertionError;
use ContentHandler;
use FormatJSON;
use PHPUnit\Framework\Assert;
use Title;
use TitleValue;
use WikiPage;

class TestStorageHelper {

	/**
	 * Coarse wrapper for creating temporary content.
	 *
	 * @param User|null $user User to edit as.
	 *
	 * @return array [ WikiPage, Revision ]
	 */
	public static function createEntity( $user = null ) {
		global $wgUser;

		if ( is_null( $user ) ) {
			$user = $wgUser;
		}

		$editTarget = new TitleValue( 0, 'JadeJudgmentContentTestPage' );
		$title = Title::newFromLinkTarget( $editTarget );
		$summary = 'Test edit';
		$page = WikiPage::factory( $title );
		$status = $page->doEditContent(
			ContentHandler::makeContent( __CLASS__, $title ),
			$summary,
			0,
			false,
			$user
		);

		Assert::assertTrue( $status->isGood() );

		$revision = $status->value['revision'];
		Assert::assertNotNull( $revision );

		return [ $page, $revision ];
	}

	/**
	 * @param string $titleStr Page title to load.
	 *
	 * @return [ WikiPage, array ] Page and decoded content.
	 */
	public static function loadJudgment( $titleStr ) {
		$target = new TitleValue( NS_JUDGMENT, $titleStr );
		$title = Title::newFromLinkTarget( $target );
		$page = WikiPage::factory( $title );
		$content = $page->getContent();
		$judgments = FormatJSON::decode( $content->getNativeData(), true );
		return [ $page, $judgments ];
	}

	/**
	 * @param string $titleStr Page title.
	 * @param string|array $text Content to save.
	 * @param User|null $user User to save as.
	 *
	 * @return bool True if successful.
	 */
	public static function saveJudgment( $titleStr, $text, $user = null ) {
		global $wgUser;
		if ( is_null( $user ) ) {
			$user = $wgUser;
		}
		if ( is_array( $text ) ) {
			$text = FormatJSON::encode( $text );
		}
		$editTarget = new TitleValue( NS_JUDGMENT, $titleStr );
		$title = Title::newFromLinkTarget( $editTarget );
		$summary = 'Test edit';
		$page = WikiPage::factory( $title );
		return $page->doEditContent(
			ContentHandler::makeContent( $text, $title ),
			$summary,
			0,
			false,
			$user
		);
	}

	/**
	 * Fine control wrapper for making raw edits.
	 *
	 * @param int $namespace Integer namespace.
	 * @param string $title Title without namespace.
	 * @param string $content New content.
	 * @param string $summary Edit summary.
	 * @param bool $expectedStatus Assert that the edit was either successful
	 *        or a failure.
	 *
	 * @return array [ 'page' => WikiPage, 'revision' => Revision ]
	 */
	public static function makeEdit( $namespace, $title, $content, $summary, $expectedStatus = true ) {
		global $wgUser;

		$editTarget = new TitleValue( $namespace, $title );
		$title = Title::newFromLinkTarget( $editTarget );
		$page = WikiPage::factory( $title );
		$status = $page->doEditContent(
			ContentHandler::makeContent( $content, $title ),
			$summary,
			0,
			false,
			$wgUser
		);

		if ( $expectedStatus !== $status->isGood() ) {
			throw new AssertionError( 'Wrong edit return status' );
		}
		if ( $expectedStatus === false ) {
			// Nothing more to do.
			return;
		}

		$revision = $status->value["revision"];
		if ( $revision === null ) {
			throw new AssertionError( 'No revision after edit' );
		}

		return [
			"page" => $page,
			"revision" => $revision,
		];
	}

}
