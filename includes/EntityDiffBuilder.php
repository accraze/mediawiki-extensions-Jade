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

use Article;
use ChangesList;
use ChangeTags;
use Html;
use Linker;
use MediaWiki\Linker\LinkRenderer;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\NameTableAccessException;
use RequestContext;
use Revision;
use Title;

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

	/** @var Article */
	protected $article;

	/** @var Revision */
	protected $mNewRev;

	/** @var Revision */
	protected $mOldRev;

	/** @var string|bool */
	protected $mOldTags;

	/** @var string|bool */
	protected $mNewTags;

	public function __construct() {
		$this->linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
	}

	public function getRevisionsFromTitle( Title $title ) {
		$id = explode( '/', $title )[1];
		$this->article = Article::newFromId( (int)$id );
		$this->mNewRev = $this->article->getRevision();
		$this->mOldRev = $this->mNewRev->getPrevious();
	}

	public function buildDiffHeader( Title $title ) {
		$this->getRevisionsFromTitle( $title );
		$this->loadTags();
		$oldHeader = $this->buildOldHeader();
		$newHeader = $this->buildNewHeader();
		$diffHeader = $oldHeader . $newHeader;
		return $diffHeader;
	}

	public function buildOldHeader() {
		if ( $this->mOldRev ) {
				$prevlink = $this->linkRenderer->makeKnownLink(
					$this->mOldRev->getTitle(),
					$this->msg( 'previousdiff' )->text(),
					[ 'id' => 'differences-prevlink' ],
					[ 'diff' => 'prev', 'oldid' => $this->mOldRev->getId() ]
				);
		} else {
				$prevlink = "\u{00A0}";
		}

		$ldel = $this->revisionDeleteLink( $this->mOldRev );
		$oldRevisionHeader = $this->getRevisionHeader( $this->mOldRev, 'complete' );
		$oldChangeTags = ChangeTags::formatSummaryRow( $this->mOldTags, 'diff', $this->getContext() );

		$oldHeader = '<td class="diff-otitle" colspan="2">' .
			'<div id="mw-diff-otitle6"><strong>' . $this->mOldRev->getTitle() . '</strong></div>' .
			'<div id="mw-diff-otitle1"><strong>' .
			$oldRevisionHeader . '</strong></div>' .
			'<div id="mw-diff-otitle2">' .
			Linker::revUserTools( $this->mOldRev, false ) . '</div>' .
			'<div id="mw-diff-otitle3">' .
			Linker::revComment( $this->mOldRev, false, true ) . $ldel . '</div>' .
			'<div id="mw-diff-otitle5">' . $oldChangeTags[0] . '</div>' .
			'<div id="mw-diff-otitle4">' . $prevlink . '</div></td>';
		return $oldHeader;
	}

	public function buildNewHeader() {
		if ( !$this->mNewRev->isCurrent() ) {
			$nextlink = $this->linkRenderer->makeKnownLink(
				$this->mNewRev->getTitle(),
				$this->msg( 'nextdiff' )->text(),
				[ 'id' => 'differences-nextlink' ],
				[ 'diff' => 'next', 'oldid' => $this->mNewRev->getId() ]
			);
		} else {
			$nextlink = "\u{00A0}";
		}

		if ( $this->mNewRev->isMinor() ) {
			$newminor = ChangesList::flag( 'minor' );
		} else {
			$newminor = '';
		}

		# Handle RevisionDelete links...
		$rdel = $this->revisionDeleteLink( $this->mNewRev );

		$newRevisionHeader = $this->getRevisionHeader( $this->mNewRev, 'complete' );
		$newChangeTags = ChangeTags::formatSummaryRow( $this->mNewTags, 'diff', $this->getContext() );

		$newHeader = '<td class="diff-ntitle" colspan="2">' .
			'<div id="mw-diff-otitle6"><strong>' . $this->mNewRev->getTitle() . '</strong></div>' .
			'<div id="mw-diff-ntitle1"><strong>' .
			$newRevisionHeader . '</strong></div>' .
			'<div id="mw-diff-ntitle2">' . Linker::revUserTools( $this->mNewRev, true ) .
			"</div>" .
			'<div id="mw-diff-ntitle3">' . $newminor .
			Linker::revComment( $this->mNewRev, true, true ) . $rdel . '</div>' .
			'<div id="mw-diff-ntitle5">' . $newChangeTags[0] . '</div>' .
			'<div id="mw-diff-ntitle4">' . $nextlink . '</div></td>';
		return $newHeader;
	}

	/**
	 * Get a header for a specified revision.
	 *
	 * @param Revision $rev
	 * @param string $complete 'complete' to get the header wrapped depending
	 *        the visibility of the revision and a link to edit the page.
	 *
	 * @return string HTML fragment
	 */
	public function getRevisionHeader( Revision $rev, $complete = '' ) {
		$lang = $this->getLanguage();
		$user = $this->getUser();
		$revtimestamp = $rev->getTimestamp();
		$timestamp = $lang->userTimeAndDate( $revtimestamp, $user );
		$dateofrev = $lang->userDate( $revtimestamp, $user );
		$timeofrev = $lang->userTime( $revtimestamp, $user );

		$header = $this->msg(
			$rev->isCurrent() ? 'currentrev-asof' : 'revisionasof',
			$timestamp,
			$dateofrev,
			$timeofrev
		);

		if ( $complete !== 'complete' ) {
			return $header->escaped();
		}

		$title = $rev->getTitle();

		$header = $this->linkRenderer->makeKnownLink( $title, $header->text(), [],
			[ 'oldid' => $rev->getId() ] );

		if ( $this->userCanEdit( $rev ) ) {
			$editQuery = [ 'action' => 'edit' ];
			if ( !$rev->isCurrent() ) {
				$editQuery['oldid'] = $rev->getId();
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
			if ( $rev->isDeleted( RevisionRecord::DELETED_TEXT ) ) {
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
		if ( $this->mOldRev === null ) {
			$oldRevId = null;
		} else {
			$oldRevId = $this->mOldRev->getId();
		}
		if ( $this->mOldRev !== false ) {
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
		if ( $this->mNewRev === null ) {
			$newRevId = null;
		} else {
			$newRevId = $this->mNewRev->getId();
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
	 * @param Revision $rev
	 * @return bool whether the user can see and edit the revision.
	 */
	private function userCanEdit( Revision $rev ) {
		$user = $this->getUser();

		if ( !$rev->userCan( RevisionRecord::DELETED_TEXT, $user ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param Revision $rev
	 *
	 * @return string
	 */
	protected function revisionDeleteLink( $rev ) {
		$link = Linker::getRevDeleteLink( $this->getUser(), $rev, $rev->getTitle() );
		if ( $link !== '' ) {
			$link = "\u{00A0}\u{00A0}\u{00A0}" . $link . ' ';
		}
		return $link;
	}
}
