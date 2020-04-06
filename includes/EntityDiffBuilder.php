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

use ChangesList;
use ChangeTags;
use Html;
use Linker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\NameTableAccessException;
use RequestContext;
use Title;
use TitleFormatter;

/**
 * Create Diff header for Entity view.
 * Loosley based on Mediawiki\DifferenceEngine
 *
 * @license GPL-3.0-or-later
 */

class EntityDiffBuilder {

	/** @var RequestContext */
	protected $context;

	/** @var LinkRenderer */
	protected $linkRenderer;

	/** @var RevisionLookup */
	private $revLookup;

	/** @var TitleFormatter */
	private $titleFormatter;

	/** @var RevisionRecord */
	private $mNewRevRecord;

	/** @var RevisionRecord */
	private $mOldRevRecord;

	/** @var string|bool */
	protected $mOldTags;

	/** @var string|bool */
	protected $mNewTags;

	public function __construct() {
		$services = MediaWikiServices::getInstance();
		$this->linkRenderer = $services->getLinkRenderer();
		$this->revLookup = $services->getRevisionLookup();
		$this->titleFormatter = $services->getTitleFormatter();
	}

	public function getRevisionsFromTitle( Title $title ) {
		$id = explode( '/', $title )[1];

		$this->mNewRevRecord = $this->revLookup->getRevisionById( (int)$id );
		$this->mOldRevRecord = $this->revLookup->getPreviousRevision( $this->mNewRevRecord );
	}

	public function buildDiffHeader( Title $title ) {
		$this->getRevisionsFromTitle( $title );
		$this->loadTags();
		if ( $this->mOldRevRecord !== null ) {
			$oldHeader = $this->buildOldHeader();
		} else {
			$oldHeader = '';
		}
		$newHeader = $this->buildNewHeader();
		$diffHeader = $oldHeader . $newHeader;
		return $diffHeader;
	}

	public function buildOldHeader() {
		$oldRevRecord = $this->mOldRevRecord;
		if ( $oldRevRecord ) {
			$prevlink = $this->linkRenderer->makeKnownLink(
				$oldRevRecord->getPageAsLinkTarget(),
				$this->msg( 'previousdiff' )->text(),
				[ 'id' => 'differences-prevlink' ],
				[ 'diff' => 'prev', 'oldid' => $oldRevRecord->getId() ]
			);
		} else {
			$prevlink = "\u{00A0}";
		}

		// Previously it was assumed that mOldRev was a Revision, even though it could have
		// been null, and this follows that behaviour, since the code was designed with
		// the assumption that it wasn't null
		// TODO fix me what should happen if the previous RevRecord is null? I guess
		// the EntityDiffBuilder requires it to have existed?
		'@phan-var RevisionRecord $oldRevRecord';

		$ldel = $this->revisionDeleteLink( $oldRevRecord );
		$oldRevisionHeader = $this->getRevisionHeader( $oldRevRecord, 'complete' );
		$oldChangeTags = ChangeTags::formatSummaryRow( $this->mOldTags, 'diff', $this->getContext() );

		$prefixedText = $this->titleFormatter->getPrefixedText( $oldRevRecord->getPageAsLinkTarget() );
		$oldHeader = '<td class="diff-otitle" colspan="2">' .
			'<div id="mw-diff-otitle6"><strong>' . $prefixedText . '</strong></div>' .
			'<div id="mw-diff-otitle1"><strong>' .
			$oldRevisionHeader . '</strong></div>' .
			'<div id="mw-diff-otitle2">' .
			Linker::revUserTools( $oldRevRecord, false ) . '</div>' .
			'<div id="mw-diff-otitle3">' .
			Linker::revComment( $oldRevRecord, false, true ) . $ldel . '</div>' .
			'<div id="mw-diff-otitle5">' . $oldChangeTags[0] . '</div>' .
			'<div id="mw-diff-otitle4">' . $prevlink . '</div></td>';
		return $oldHeader;
	}

	public function buildNewHeader() {
		$newRevRecord = $this->mNewRevRecord;
		if ( !$newRevRecord->isCurrent() ) {
			$nextlink = $this->linkRenderer->makeKnownLink(
				$newRevRecord->getPageAsLinkTarget(),
				$this->msg( 'nextdiff' )->text(),
				[ 'id' => 'differences-nextlink' ],
				[ 'diff' => 'next', 'oldid' => $newRevRecord->getId() ]
			);
		} else {
			$nextlink = "\u{00A0}";
		}

		if ( $newRevRecord->isMinor() ) {
			$newminor = ChangesList::flag( 'minor' );
		} else {
			$newminor = '';
		}

		# Handle RevisionDelete links...
		$rdel = $this->revisionDeleteLink( $newRevRecord );

		$newRevisionHeader = $this->getRevisionHeader( $newRevRecord, 'complete' );
		$newChangeTags = ChangeTags::formatSummaryRow( $this->mNewTags, 'diff', $this->getContext() );

		$prefixedText = $this->titleFormatter->getPrefixedText( $newRevRecord->getPageAsLinkTarget() );
		$newHeader = '<td class="diff-ntitle" colspan="2">' .
			'<div id="mw-diff-ntitle6"><strong>' . $prefixedText . '</strong></div>' .
			'<div id="mw-diff-ntitle1"><strong>' .
			$newRevisionHeader . '</strong></div>' .
			'<div id="mw-diff-ntitle2">' . Linker::revUserTools( $newRevRecord, true ) .
			"</div>" .
			'<div id="mw-diff-ntitle3">' . $newminor .
			Linker::revComment( $newRevRecord, true, true ) . $rdel . '</div>' .
			'<div id="mw-diff-ntitle5">' . $newChangeTags[0] . '</div>' .
			'<div id="mw-diff-ntitle4">' . $nextlink . '</div></td>';
		return $newHeader;
	}

	/**
	 * Get a header for a specified revision.
	 *
	 * @param RevisionRecord $revRecord
	 * @param string $complete 'complete' to get the header wrapped depending
	 *        the visibility of the revision and a link to edit the page.
	 *
	 * @return string HTML fragment
	 */
	private function getRevisionHeader( RevisionRecord $revRecord, $complete = '' ) {
		$lang = $this->getLanguage();
		$user = $this->getUser();
		$revtimestamp = $revRecord->getTimestamp();
		$timestamp = $lang->userTimeAndDate( $revtimestamp, $user );
		$dateofrev = $lang->userDate( $revtimestamp, $user );
		$timeofrev = $lang->userTime( $revtimestamp, $user );

		$header = $this->msg(
			$revRecord->isCurrent() ? 'currentrev-asof' : 'revisionasof',
			$timestamp,
			$dateofrev,
			$timeofrev
		);

		if ( $complete !== 'complete' ) {
			return $header->escaped();
		}

		$title = $revRecord->getPageAsLinkTarget();

		$header = $this->linkRenderer->makeKnownLink( $title, $header->text(), [],
			[ 'oldid' => $revRecord->getId() ] );

		if ( $this->userCanEdit( $revRecord ) ) {
			$editQuery = [ 'action' => 'edit' ];
			if ( !$revRecord->isCurrent() ) {
				$editQuery['oldid'] = $revRecord->getId();
			}

			$key = MediaWikiServices::getInstance()->getPermissionManager()
				->quickUserCan( 'edit', $user, $title ) ? 'editold' : 'viewsourceold';
			$msg = $this->msg( $key )->text();
			$editLink = $this->msg( 'parentheses' )->rawParams(
				$this->linkRenderer->makeKnownLink( $title, $msg, [], $editQuery ) )->escaped();
			$header .= ' ' . Html::rawElement(
				'span',
				[ 'class' => 'mw-diff-edit' ],
				$editLink
			);
			if ( $revRecord->isDeleted( RevisionRecord::DELETED_TEXT ) ) {
				$header = Html::rawElement(
					'span',
					[ 'class' => 'history-deleted' ],
					$header
				);
			}
		} else {
			$header = Html::rawElement( 'span', [ 'class' => 'history-deleted' ], $header );
		}
		return $header;
	}

	/**
	 * Get the base IContextSource object
	 * @return \IContextSource
	 */
	public function getContext() {
		if ( $this->context === null ) {
			$class = static::class;
			wfDebug( __METHOD__ . " ($class): called and \$context is null. " .
				"Using RequestContext::getMain() for sanity\n" );
			$this->context = RequestContext::getMain();
		}

		return $this->context;
	}

	public function loadTags() {
		// Load tags information for both revisions
		$dbr = wfGetDB( DB_REPLICA );
		$changeTagDefStore = MediaWikiServices::getInstance()->getChangeTagDefStore();
		if ( $this->mOldRevRecord === null ) {
			$oldRevId = null;
		} else {
			$oldRevId = $this->mOldRevRecord->getId();
		}
		// FIXME $oldRevRecord is never false
		if ( $this->mOldRevRecord !== false ) {
			$tagIds = $dbr->selectFieldValues(
				'change_tag',
				'ct_tag_id',
				[ 'ct_rev_id' => $oldRevId ],
				__METHOD__
			);
			$tags = [];
			foreach ( $tagIds as $tagId ) {
				try {
					$tags[] = $changeTagDefStore->getName( (int)$tagId );
				} catch ( NameTableAccessException $exception ) {
					continue;
				}
			}
			$this->mOldTags = implode( ',', $tags );
		} else {
			$this->mOldTags = false;
		}
		if ( $this->mNewRevRecord === null ) {
			$newRevId = null;
		} else {
			$newRevId = $this->mNewRevRecord->getId();
		}

		$tagIds = $dbr->selectFieldValues(
			'change_tag',
			'ct_tag_id',
			[ 'ct_rev_id' => $newRevId ],
			__METHOD__
		);
		$tags = [];
		foreach ( $tagIds as $tagId ) {
			try {
				$tags[] = $changeTagDefStore->getName( (int)$tagId );
			} catch ( NameTableAccessException $exception ) {
				continue;
			}
		}
		$this->mNewTags = implode( ',', $tags );
	}

	/**
	 * Get a Message object with context set
	 * Parameters are the same as wfMessage()
	 *
	 * @param string|string[]|\MessageSpecifier $key Message key, or array of keys,
	 *   or a MessageSpecifier.
	 * @param mixed ...$params
	 * @return \Message
	 */
	public function msg( $key, ...$params ) {
		return $this->getContext()->msg( $key, ...$params );
	}

	/**
	 * @return \User
	 */
	public function getUser() {
		return $this->getContext()->getUser();
	}

	/**
	 * @return \Language
	 */
	public function getLanguage() {
		return $this->getContext()->getLanguage();
	}

	/**
	 * @param RevisionRecord $revRecord
	 * @return bool whether the user can see and edit the revision.
	 */
	private function userCanEdit( RevisionRecord $revRecord ) {
		$user = $this->getUser();

		if ( !$revRecord->audienceCan(
			RevisionRecord::DELETED_TEXT,
			RevisionRecord::FOR_THIS_USER,
			$user
		) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param RevisionRecord $revRecord
	 *
	 * @return string
	 */
	private function revisionDeleteLink( RevisionRecord $revRecord ) {
		$link = Linker::getRevDeleteLink(
			$this->getUser(),
			$revRecord,
			$revRecord->getPageAsLinkTarget()
		);
		if ( $link !== '' ) {
			$link = "\u{00A0}\u{00A0}\u{00A0}" . $link . ' ';
		}
		return $link;
	}
}
