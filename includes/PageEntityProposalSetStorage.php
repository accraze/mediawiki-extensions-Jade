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

namespace Jade;

use ChangeTags;
use FormatJson;
use Jade\Content\EntityContent;
use MediaWiki\MediaWikiServices;
use Status;
use StatusValue;
use Title;
use User;
use WikiPage;

/**
 * Backend to store Proposals as wiki pages under a new namespace.
 */
class PageEntityProposalSetStorage implements EntityProposalSetStorage {

	/**
	 * @param ProposalTarget $target identity of target wiki entity.
	 * @param array $proposalSet All Proposals on this entity, as nested
	 * associative arrays, normalized for storage.
	 * @param string $summary Edit summary.
	 * @param User $user User to attribute to
	 * @param array $tags Optional list of change tags to set on the revision being created.
	 *
	 * @return StatusValue isOK if the edit was successful.
	 */
	public function storeProposalSet(
		ProposalTarget $target,
		array $proposalSet,
		$summary,
		User $user,
		array $tags
	) {
		$title = TitleHelper::buildJadeTitle( $target );
		$dbTitle = Title::newFromTitleValue( $title );
		$page = WikiPage::factory( $dbTitle );
		$pm = MediaWikiServices::getInstance()->getPermissionManager();

		// TODO: Why aren't these permissions checks already handled by
		// doEditContent?
		if ( !$page->exists() ) {
			if ( !$pm->userCan( 'create', $user, $dbTitle ) ) {
				return Status::newFatal( 'jade-cannot-create-page' );
			}
		}
		// `edit` contains checks not present in `create`, do those as well.
		if ( !$pm->userCan( 'edit', $user, $dbTitle ) ) {
			return Status::newFatal( 'jade-cannot-edit-page' );
		}
		if ( count( $tags ) > 0 ) {
			$status = ChangeTags::canAddTagsAccompanyingChange( $tags, $user );
			if ( !$status->isOK() ) {
				return $status;
			}
		}

		// Serialize to a new proposal ContentHandler.
		$proposalText = FormatJson::encode( $proposalSet, true, 0 );
		$content = new EntityContent( $proposalText );

		// TODO: Migrate to use the PageUpdater API once it matures.
		return $page->doEditContent(
			$content,
			$summary,
			0,
			false,
			$user,
			null,
			$tags
		);
	}

	/**
	 * @param ProposalTarget $target identity of target wiki entity.
	 *
	 * @return StatusValue with array value containing all proposals for this
	 *         entity.
	 */
	public function loadProposalSet( ProposalTarget $target ) {
		$title = TitleHelper::buildJadeTitle( $target );
		$dbTitle = Title::newFromTitleValue( $title );
		$page = WikiPage::factory( $dbTitle );

		$currentContent = $page->getContent();
		// Return content as an associative array.
		if ( $currentContent !== null ) {
			$currentProposal = FormatJson::decode( $currentContent->getNativeData(), true );
		} else {
			$currentProposal = [];
		}
		return Status::newGood( $currentProposal );
	}

}
