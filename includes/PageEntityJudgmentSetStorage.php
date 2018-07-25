<?php

namespace JADE;

use FormatJSON;
use MWException;
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
	 * @throws MWException The edit could not be made.
	 */
	public function storeJudgmentSet( $entityType, $entityId, $judgmentSet, $summary, $tags ) {
		global $wgUser;

		$title = TitleHelper::buildJadeTitle( $entityType, $entityId );
		$page = WikiPage::factory( $title );

		// TODO: Why aren't these permissions checks already handled by
		// doEditContent?
		if ( !$page->exists() ) {
			if ( !$page->getTitle()->userCan( 'create', $wgUser ) ) {
				throw new MWException( 'You cannot create this page.' );
			}
		}
		// `edit` contains checks not present in `create`, do those as well.
		if ( !$page->getTitle()->userCan( 'edit', $wgUser ) ) {
			throw new MWException( 'You cannot edit this page.' );
		}
		if ( count( $tags ) > 0 ) {
			$status = ChangeTags::canAddTagsAccompanyingChange( $tags, $wgUser );
			if ( !$status->isOK() ) {
				throw new MWException( 'User cannot add requested tags: ' . $status->getHTML() );
			}
		}

		// Serialize to a new judgment ContentHandler.
		$judgmentText = FormatJSON::encode( $judgmentSet, true, 0 );
		$content = new JudgmentContent( $judgmentText );

		// TODO: Migrate to use the PageUpdater API once it matures.
		$status = $page->doEditContent(
			$content,
			$summary,
			0,
			false,
			$wgUser,
			null,
			$tags
		);
		if ( !$status->isOK() ) {
			throw new MWException( 'Failed to store judgment: ' . $status->getMessage() );
		}
	}

	/**
	 * @param string $entityType Name of wiki entity type, in lowercase.
	 * @param int $entityId Page ID or Revision ID of the entity.
	 *
	 * FIXME: gross side-effect.return signature
	 * @return array [ WikiPage $page, array $judgmentSet ]
	 * $judgmentSet contains All judgments for this entity.
	 */
	public function loadJudgmentSet( $entityType, $entityId ) {
		$title = TitleHelper::buildJadeTitle( $entityType, $entityId );
		$page = WikiPage::factory( $title );

		$currentContent = $page->getContent();
		// Return content as an associative array.
		if ( $currentContent !== null ) {
			$currentJudgment = FormatJSON::decode( $currentContent->getNativeData(), true );
		} else {
			$currentJudgment = [];
		}
		return [ $page, $currentJudgment ];
	}

}
