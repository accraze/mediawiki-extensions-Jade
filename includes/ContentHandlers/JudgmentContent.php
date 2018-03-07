<?php

namespace JADE\ContentHandlers;

use JsonContent;
use MediaWiki\MediaWikiServices;
use Status;
use User;
use WikiPage;

class JudgmentContent extends JsonContent {
	const JUDGMENT_SCHEMA = '/../../jsonschema/judgment/v1.json';
	const SCORING_SCHEMA_ROOT = '/../../jsonschema/scoring';

	public function __construct( $text, $modelId = 'JadeJudgment' ) {
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
	 * @param User $user
	 *
	 * @return Status A status object, which isGood() if we should continue
	 * with the save.
	 *
	 * @see Content::prepareSave
	 */
	public function prepareSave( WikiPage $page, $flags, $parentRevId, User $user ) {
		$status = parent::prepareSave( $page, $flags, $parentRevId, $user );

		if ( $status->isOK() ) {
			$validator = MediaWikiServices::getInstance()->getService(
				'JADEJudgmentValidator'
			);
			$data = $this->getData()->getValue();
			if ( !$validator->validatePageTitle( $page, $data ) ) {
				return Status::newFatal( 'invalid-content-data' );
			}
		}

		return $status;
	}

	/**
	 * Check that the judgment content is well-formed.
	 *
	 * @return bool True if the content is valid.
	 *
	 * @see Content::isValid
	 */
	public function isValid() {
		if ( !parent::isValid() ) {
			return false;
		}

		// Special case to allow for empty content when first creating a page.
		if ( $this->isEmpty() ) {
			return true;
		}

		$data = $this->getData()->getValue();
		$validator = MediaWikiServices::getInstance()->getService(
			'JADEJudgmentValidator'
		);
		return $validator->validateJudgmentContent( $data );
	}

	public function isEmpty() {
		return count( (array)$this->getData()->getValue() ) === 0;
	}

}
