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

namespace Jade\Hooks;

use Content;
use Jade\JadeServices;
use Jade\JudgmentTarget;
use Jade\TitleHelper;
use LogEntry;
use MediaWiki\Logger\LoggerFactory;
use Revision;
use Title;
use TitleValue;
use User;
use WikiPage;

class LinkTableHooks {

	/**
	 * Update link tables after a new judgment page is inserted.
	 *
	 * @param WikiPage &$judgmentPage WikiPage created
	 * @param User &$user User creating the article
	 * @param Content $content New content as a Content object
	 * @param string $summary Edit summary/comment
	 * @param bool $isMinor Whether or not the edit was marked as minor
	 * @param null $isWatch (No longer used)
	 * @param null $section (No longer used)
	 * @param int &$flags Flags passed to WikiPage::doEditContent()
	 * @param Revision $revision New Revision of the article
	 *
	 * TODO: move to a deferred update?
	 */
	public static function onPageContentInsertComplete(
		WikiPage &$judgmentPage,
		User &$user,
		Content $content,
		$summary,
		$isMinor,
		$isWatch,
		$section,
		&$flags,
		Revision $revision
	) {
		$target = self::judgmentTarget( $judgmentPage->getTitle()->getTitleValue() );
		if ( !$target ) {
			return;
		}

		JadeServices::getJudgmentIndexStorage()
			->insertIndex( $target, $judgmentPage );
	}

	/**
	 * Remove link when judgment page is deleted.
	 *
	 * @param WikiPage &$judgmentPage the article that was deleted.
	 * @param User &$user the user that deleted the article
	 * @param string $reason the reason the article was deleted
	 * @param int $id id of the article that was deleted
	 * @param Content $content the content of the deleted article
	 * @param LogEntry $logEntry the log entry used to record the deletion
	 */
	public static function onArticleDeleteComplete(
		WikiPage &$judgmentPage,
		User &$user,
		$reason,
		$id,
		Content $content,
		LogEntry $logEntry
	) {
		$target = self::judgmentTarget( $judgmentPage->getTitle()->getTitleValue() );
		if ( !$target ) {
			return;
		}

		JadeServices::getJudgmentIndexStorage()
			->deleteIndex( $target, $judgmentPage );
	}

	/**
	 * Restore link when a judgment page is undeleted.
	 *
	 * @param Title $title the article being restored
	 * @param bool $create Whether or not the restoration caused the page to be
	 *        created (i.e. it didn't exist before)
	 * @param string $comment Comment explaining the undeletion
	 * @param int $oldPageId ID of page previously deleted (from archive
	 *        table). This ID will be used for the restored page.
	 * @param array $restoredPages Set of page IDs that have revisions restored
	 *        for the undelete, with keys being page IDs and values are 'true'.
	 */
	public static function onArticleUndelete(
		Title $title,
		$create,
		$comment,
		$oldPageId,
		array $restoredPages
	) {
		if ( !$create ) {
			// Page already exists, don't create a new link.
			return;
		}
		$target = self::judgmentTarget( $title->getTitleValue() );
		if ( !$target ) {
			return;
		}

		$judgmentPage = WikiPage::newFromID( $oldPageId );
		JadeServices::getJudgmentIndexStorage()
			->insertIndex( $target, $judgmentPage );
	}

	/**
	 * Wrapper around TitleHelper::parseTitleValue, logging errors if appropriate.
	 *
	 * @param TitleValue $title judgment page title to parse.
	 *
	 * @return JudgmentTarget|null Judgment target, or null if the title
	 *         couldn't be parsed.
	 */
	private static function judgmentTarget( TitleValue $title ) {
		$status = TitleHelper::parseTitleValue( $title );
		if ( !$status->isOK() ) {
			if ( $title->getNamespace() === NS_JUDGMENT ) {
				// Should be unreachable thanks to JudgmentValidator.  If
				// something did go wrong, it should be logged and
				// investigated.
				// TODO: Should this be a responsibility of TitleHelper::parseTitleValue()?
				$logger = LoggerFactory::getInstance( 'Jade' );
				$logger->error( "Cannot parse judgment title: {$status}" );
			}
			return null;
		}
		return $status->value;
	}

}
