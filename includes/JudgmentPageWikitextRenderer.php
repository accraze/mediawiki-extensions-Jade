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

use CentralIdLookup;
use LogicException;
use MediaWiki\MediaWikiServices;
use TemplateParser;
use User;

/**
 * Renders judgments into wikitext, suitable for rendering a judgment page.
 *
 * TODO: Review whether we should be using content language, e.g. if translate
 * tags are present in the freeform wikitext fields.
 */
class JudgmentPageWikitextRenderer {

	const TEMPLATES_PATH = '../templates';

	/** Template to render a judgment page as wikitext. */
	const JUDGMENT_PAGE_TEMPLATE = 'judgment_page.wiki';

	private $templateRenderer;

	public function __construct() {
		$templatePath = __DIR__ . '/' . self::TEMPLATES_PATH;
		$this->templateRenderer = new TemplateParser( $templatePath );
	}

	/**
	 * Render as wikitext.
	 *
	 * @param object $judgmentData Judgment page content, decoded as a stdClass.
	 *
	 * @return string Wikitext representation.
	 */
	public function getWikitext( $judgmentData ) {
		// Transform raw judgment data into parameters and wikitext suitable
		// for use in the template.
		$judgmentList = [];
		foreach ( $judgmentData->judgments as $rawJudgment ) {
			$judgment = [];

			// Notes are literal wikitext, or a placeholder if empty.
			$judgment['notes'] = $rawJudgment->notes ?? '';

			$judgment['value'] = $this->getSchemaSummary( $rawJudgment->schema );

			$judgment['preferred'] = $rawJudgment->preferred;

			if ( property_exists( $rawJudgment, 'endorsements' ) ) {
				$judgment['hasEndorsements'] = true;
				$judgment['endorsements'] = $this->getEndorsements( $rawJudgment->endorsements );
			}

			$judgmentList[] = $judgment;
		}
		$params['judgments'] = $judgmentList;

		return $this->templateRenderer->processTemplate(
			self::JUDGMENT_PAGE_TEMPLATE,
			$params );
	}

	/**
	 * Produce a human-readable summary of the judgment schema value.
	 *
	 * @param object $schemaList Judgment's schema values, as a map from schema
	 *        name to judgment value for that schema.
	 *
	 * @return string Wikitext snippet summarizing the values.
	 */
	private function getSchemaSummary( $schemaList ) {
		$contentLanguage = MediaWikiServices::getInstance()->getContentLanguage();

		// Convert schema values to a human-readable summary of the judgment.
		$schemaTextList = [];
		foreach ( $schemaList as $schemaName => $value ) {
			// Human text for this judgment schema's value.
			$schemaTextList[] = $this->getSchemaValueText( $schemaName, $value );
		}
		return $contentLanguage->listToText( $schemaTextList );
	}

	private function getSchemaValueText( $schemaName, $value ) {
		if ( is_bool( $value ) ) {
			$valueStr = $value ? 'true' : 'false';
		} else {
			$valueStr = strtolower( strval( $value ) );
		}

		$calculatedMessageKey = "jade-{$schemaName}-{$valueStr}";
		$message = wfMessage( $calculatedMessageKey );

		if ( $message->exists() ) {
			// If a message exists for this exact value, use it.
			return $message->inContentLanguage()->text();
		} elseif ( $schemaName === 'contentquality' ) {
			// Generic statement for an unknown content quality key.  For wikis
			// that override the article quality scale, we'll need to figure
			// out if it's appropriate to include the custom keys in the
			// extension, or if these should be provided as local wiki strings.
			return wfMessage( 'jade-contentquality-generic', $value )->inContentLanguage()->text();
		}

		// It should be impossible to get here.  Fall through by displaying the
		// regular missing translation message.
		return $message->inContentLanguage()->text();
	}

	/**
	 * Return a human-readable list of endorsements.
	 *
	 * @param array $endorsementList Endorsements as a list of stdClass.
	 *
	 * @return string[] Wikitext list of endorsements, usually multi-line.
	 */
	private function getEndorsements( array $endorsementList ) {
		$endorsementTextList = [];
		foreach ( $endorsementList as $rawEndorsement ) {
			$endorsement = [];

			// Build a user label for the endorsement author.
			$endorsement['user'] = $this->getUserWikitext( $rawEndorsement->user );

			// Comments are literal wikitext.
			$endorsement['comment'] = $rawEndorsement->comment ?? '';

			$endorsementTextList[] = $endorsement;
		}
		return $endorsementTextList;
	}

	/**
	 * Render global user as wikitext.
	 *
	 * @param object $user Contains either an IP or CentralAuth user ID.
	 *
	 * @return string Linked wikitext for the user.
	 */
	private function getUserWikitext( $user ) {
		if ( property_exists( $user, 'cid' ) ) {
			// Look up central user.
			$username = CentralIdLookup::factory()->nameFromCentralId( $user->cid );
			if ( $username === null ) {
				// Missing users or suppressed usernames will be stubbed.
				// FIXME: We probably want an i18n message here like "User <id> missing".
				return "⧼{$user->cid}⧽";
			} else {
				return "[[User:{$username}]]";
			}
		} elseif ( property_exists( $user, 'id' ) ) {
			// Look up local ID.
			$localUsername = User::whoIs( $user->id );
			if ( $localUsername === false || User::newFromId( $user->id )->isHidden() ) {
				// Missing or blocked user.
				// FIXME: Make the error more helpful.
				return "⧼{$user->id}⧽";
			} else {
				return "[[User:{$localUsername}]]";
			}
		} elseif ( property_exists( $user, 'ip' ) ) {
			return "[[User:{$user->ip}]]";
		}

		throw new LogicException( 'Broken user data structure' );
	}

}
