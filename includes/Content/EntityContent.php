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

namespace Jade\Content;

use Jade\EntityDiffBuilder;
use Jade\EntityPageWikitextRenderer;
use Jade\JadeServices;
use JsonContent;
use MediaWiki\MediaWikiServices;
use OutputPage;
use ParserOptions;
use ParserOutput;
use Status;
use StatusValue;
use Title;
use User;
use WikiPage;

class EntityContent extends JsonContent {
	const CONTENT_MODEL_ENTITY = 'JadeEntity';
	const ENTITY_SCHEMA = '/../../jsonschema/proposal/v2.json';

	public function __construct( $text, $modelId = self::CONTENT_MODEL_ENTITY ) {
		parent::__construct( $text, $modelId );
	}

	/**
	 * Hook to validate `save` operation.
	 *
	 * Our implementation enforces the page title, making sure it's consistent
	 * with the wiki entity and what's being judged.
	 *
	 * @param WikiPage $page The page to be saved.
	 * @param int $flags Bitfield for use with EDIT_XXX constants, see
	 * WikiPage::doEditContent()
	 * @param int $parentRevId The ID of the current revision
	 * @param User $user User taking action, for permissions checks.
	 *
	 * @return Status A status object, which isOK() if we can continue
	 * with the save.
	 *
	 * @see Content::prepareSave
	 */
	public function prepareSave( WikiPage $page, $flags, $parentRevId, User $user ) {
		// Note that we don't call the parent because isValid() is redundant
		// here.  This may change if additional tests or side-effects are added to
		// AbstractContent::prepareSave in the future.

		$status = $this->validateContent();
		if ( !$status->isOK() ) {
			return $status;
		}

		$data = $this->getData()->getValue();
		$validator = JadeServices::getProposalValidator();
		// return $validator->validatePageTitle( $page, $data );
		return Status::newGood();
	}

	/**
	 * Check that the Entity content is well-formed.
	 *
	 * @return bool True if the content is valid.
	 *
	 * @see Content::isValid
	 */
	public function isValid() {
		$status = $this->validateContent();
		return $status->isOK();
	}

	/**
	 * Use instead of isValid() when you care about the specific validation errors.
	 *
	 * @return StatusValue isOK if valid.
	 */
	public function validateContent() {
		if ( !parent::isValid() ) {
			return Status::newfatal( 'jade-bad-content-generic' );
		}

		// Special case to allow for empty content when first creating a page.
		if ( $this->isEmpty() ) {
			return Status::newGood();
		}

		$data = $this->getData()->getValue();
		$validator = JadeServices::getProposalValidator();
		return $validator->validateProposalContent( $data );
	}

	public function isEmpty() {
		return count( (array)$this->getData()->getValue() ) === 0;
	}

	/**
	 * @see AbstractContent::fillParserOutput
	 *
	 * @param Title $title Context title for parsing
	 * @param int|null $revId Revision ID (for {{REVISIONID}})
	 * @param ParserOptions $options Funny things to tell the parser.
	 * @param bool $generateHtml Whether or not to generate HTML
	 * @param ParserOutput &$output The output object to fill (reference).
	 */
	public function fillParserOutput(
		Title $title,
		$revId,
		ParserOptions $options,
		$generateHtml,
		ParserOutput &$output
	) {
		if ( !$this->isValid() ) {
			// We can't proceed.  JsonContent has a TODO mentioning that this
			// condition will be deprecated in the future.
			$output->setText( '' );
			return;
		}

		$parser = MediaWikiServices::getInstance()->getParser();
		$renderer = new EntityPageWikitextRenderer;
		$wikitext = $renderer->getWikitext( $this->getData()->getValue() );

		if ( $generateHtml ) {
			$output->setEnableOOUI( true );
			OutputPage::setupOOUI();
			$diffBuilder = new EntityDiffBuilder();
			try {
				$diffHeader = $diffBuilder->buildDiffHeader( $title );
			} catch ( \Throwable $e ) {
				$diffHeader = '';
			}
			$entityData = $this->getData()->getValue();
			global $wgServer;
			$jsConfigVars = [
				'entityData' => $entityData,
				'entityTitle' => $title,
				'entityId' => $revId,
				'baseUrl' => $wgServer,
				'diffHeader' => $diffHeader
			];
			$output->addHeadItem(
				'<meta name="viewport" content="width=device-width, initial-scale=1">',
				'viewport'
			);
			$output->addJsConfigVars( $jsConfigVars );
			$output->addModules( [ 'ext.Jade.entityView', 'jade.api','jade.widgets', 'jade.dialogs' ] );
		}
	}
}
