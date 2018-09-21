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

namespace JADE\Content;

use JADE\JADEServices;
use JsonContent;
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

		$data = $this->getData()->getValue();
		$status = $this->validateContent( $data );
		if ( !$status->isOK() ) {
			return $status;
		}

		$validator = JADEServices::getJudgmentValidator();
		return $validator->validatePageTitle( $page, $data );
	}

	/**
	 * Check that the judgment content is well-formed.
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
		$validator = JADEServices::getJudgmentValidator();
		return $validator->validateJudgmentContent( $data );
	}

	public function isEmpty() {
		return count( (array)$this->getData()->getValue() ) === 0;
	}

}