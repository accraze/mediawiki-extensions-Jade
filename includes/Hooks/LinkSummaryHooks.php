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
use Jade\EntitySummarizer;
use Jade\JadeServices;
use Jade\TitleHelper;
use MediaWiki\Logger\LoggerFactory;
use Revision;
use Status;
use User;
use WikiPage;

class LinkSummaryHooks {

	/**
	 * Update link summary after a judgment page is edited.
	 *
	 * @param WikiPage &$judgmentPage WikiPage modified
	 * @param User &$user User performing the modification
	 * @param Content $content New content
	 * @param string $summary Edit summary/comment
	 * @param bool $isMinor Whether or not the edit was marked as minor
	 * @param bool $isWatch (No longer used)
	 * @param string $section (No longer used)
	 * @param int $flags Flags passed to WikiPage::doEditContent()
	 * @param Revision $revision Revision object of the saved content. If the
	 * save did not result in the creation of a new revision (e.g. the
	 * submission was equal to the latest revision), this parameter may be null
	 * (null edits, or "no-op"). However, there are reports (see phab:T128838)
	 * that it's instead set to that latest revision.
	 * @param Status $status Status object about to be returned by doEditContent()
	 * @param int|bool $baseRevId the rev ID (or false) this edit was based on
	 * @param int $undidRevId the rev ID (or 0) this edit undid - added in MW 1.30
	 */
	public static function onPageContentSaveComplete(
		WikiPage &$judgmentPage,
		User &$user,
		Content $content,
		$summary,
		$isMinor,
		$isWatch,
		$section,
		$flags,
		Revision $revision,
		Status $status,
		$baseRevId,
		$undidRevId
	) {
		$status = TitleHelper::parseTitleValue( $judgmentPage->getTitle()->getTitleValue() );
		if ( !$status->isOK() ) {
			return;
		}
		$target = $status->value;

		$status = EntitySummarizer::getSummaryFromContent( $content );
		if ( !$status->isOK() ) {
			LoggerFactory::getInstance( 'Jade' )
				->warning( 'Failed to extract judgment summary: {status}', [ 'status' => $status ] );

			return;
		}
		$summaryValues = $status->value;
		$status = JadeServices::getEntityIndexStorage()
			->updateSummary( $target, $summaryValues );

		if ( !$status->isOK() ) {
			LoggerFactory::getInstance( 'Jade' )
				->warning( 'Failed to update judgment summary: {status}', [ 'status' => $status ] );
		}
	}

}
