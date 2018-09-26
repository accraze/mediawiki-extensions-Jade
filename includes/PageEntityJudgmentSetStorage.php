<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace JADE;

use ChangeTags;
use FormatJson;
use WikiPage;

use JADE\ContentHandlers\JudgmentContent;

/**
 * Backend to store judgments as wiki pages under a new namespace.
 */
class PageEntityJudgmentSetStorage implements EntityJudgmentSetStorage {

	/**
	 * @param string $entityType Name of wiki entity type, in lowercase.
	 * @param int $entityId Page ID or Revision ID of the entity being judged.
	 * @param array $judgmentSet All judgments on this entity, as nested
	 * associative arrays, normalized for storage.
	 * @param string $summary Edit summary.
	 * @param array $tags Optional list of change tags to set on the revision being created.
	 *
	 * @return StatusValue isOK if the edit was successful.
	 */
	public function storeJudgmentSet( $entityType, $entityId, $judgmentSet, $summary, $tags ) {
		global $wgUser;

		$status = TitleHelper::buildJadeTitle( $entityType, $entityId );
		if ( !$status->isOK() ) {
			return $status;
		}
		$title = $status->value;
		$page = WikiPage::factory( $title );

		// TODO: Why aren't these permissions checks already handled by
		// doEditContent?
		if ( !$page->exists() ) {
			if ( !$page->getTitle()->userCan( 'create', $wgUser ) ) {
				return Status::newFatal( 'jade-cannot-create-page' );
			}
		}
		// `edit` contains checks not present in `create`, do those as well.
		if ( !$page->getTitle()->userCan( 'edit', $wgUser ) ) {
			return Status::newFatal( 'jade-cannot-edit-page' );
		}
		if ( count( $tags ) > 0 ) {
			$status = ChangeTags::canAddTagsAccompanyingChange( $tags, $wgUser );
			if ( !$status->isOK() ) {
				return $status;
			}
		}

		// Serialize to a new judgment ContentHandler.
		$judgmentText = FormatJson::encode( $judgmentSet, true, 0 );
		$content = new JudgmentContent( $judgmentText );

		// TODO: Migrate to use the PageUpdater API once it matures.
		return $page->doEditContent(
			$content,
			$summary,
			0,
			false,
			$wgUser,
			null,
			$tags
		);
	}

	/**
	 * @param string $entityType Name of wiki entity type, in lowercase.
	 * @param int $entityId Page ID or Revision ID of the entity.
	 *
	 * @return StatusValue with array value containing all judgments for this
	 *         entity.
	 */
	public function loadJudgmentSet( $entityType, $entityId ) {
		$status = TitleHelper::buildJadeTitle( $entityType, $entityId );
		if ( !$status->isOK() ) {
			return $status;
		}
		$title = $status->value;
		$page = WikiPage::factory( $title );

		$currentContent = $page->getContent();
		// Return content as an associative array.
		if ( $currentContent !== null ) {
			$currentJudgment = FormatJson::decode( $currentContent->getNativeData(), true );
		} else {
			$currentJudgment = [];
		}
		return Status::newGood( $currentJudgment );
	}

}