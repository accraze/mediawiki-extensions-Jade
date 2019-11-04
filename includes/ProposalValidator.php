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

use Config;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Exception\ValidationException;
use JsonSchema\Validator;
use MediaWiki\Revision\RevisionStore;
use Psr\Log\LoggerInterface;
use Status;
use StatusValue;
use WikiPage;

class ProposalValidator {
	const PROPOSAL_SCHEMA = '/../jsonschema/proposal/v2.json';

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var RevisionStore
	 */
	private $revisionStore;

	public function __construct(
		Config $config,
		LoggerInterface $logger,
		RevisionStore $revisionStore
	) {
		$this->config = $config;
		$this->logger = $logger;
		$this->revisionStore = $revisionStore;
	}

	/**
	 * Check that the proposal content is well-formed.
	 *
	 * @param object $data Data structure to validate.
	 *
	 * @return StatusValue isOK if the content is valid.
	 */
	public function validateProposalContent( $data ) {
		$status = $this->validateBasicSchema( $data );
		if ( !$status->isOK() ) {
			return $status;
		}

		$status = $this->validateEndorsementUsers( $data );
		if ( !$status->isOK() ) {
			return $status;
		}

		$status = $this->validateEndorsementTimestamps( $data );
		if ( !$status->isOK() ) {
			return $status;
		}

		$status = $this->validateContentQualityScale( $data );
		if ( !$status->isOK() ) {
			return $status;
		}

		return $this->validatePreferred( $data );
	}

	/**
	 * Ensure that the general proposal schema is followed.
	 *
	 * @param object $data Data structure to validate.
	 *
	 * @return StatusValue isOK?
	 */
	protected function validateBasicSchema( $data ) {
		return $this->validateAgainstSchema( $data, __DIR__ . self::PROPOSAL_SCHEMA );
	}

	/**
	 * Ensure that the score schemas are allowed by configuration.
	 *
	 * @param ProposalEntityType $entityType Machine name for entity type.
	 * @param object $data Data structure to validate.
	 *
	 * @return StatusValue isOK if valid.
	 */
	protected function validateEntitySchema( ProposalEntityType $entityType, $data ) {
		$allowedScoringSchemas = $this->config->get( 'JadeAllowedScoringSchemas' );
		$entityAllowedSchemas = $allowedScoringSchemas[(string)$entityType];

		foreach ( $data->proposals as $proposal ) {
			foreach ( $proposal->schema as $schemaName => $value ) {
				// Schema must be allowed.
				if ( !in_array( $schemaName, $entityAllowedSchemas ) ) {
					return Status::newFatal( 'jade-illegal-schema', $schemaName );
				}
			}
		}
		return Status::newGood();
	}

	/**
	 * Check that exactly one proposal is preferred.
	 *
	 * @param object $data Data structure to validate.
	 *
	 * @return StatusValue isOK if valid.
	 */
	protected function validatePreferred( $data ) {
		$preferredCount = 0;
		// foreach ( $data->proposals as $proposal ) {
		// if ( $proposal->preferred ?? false ) {
		// $preferredCount++;
		// }
		// }
		// if ( $preferredCount < 1 ) {
		// return Status::newFatal( 'jade-none-preferred' );
		// }
		// if ( $preferredCount > 1 ) {
		// return Status::newFatal( 'jade-too-many-preferred' );
		// }

		return Status::newGood();
	}

	/**
	 * Special handling for the contentquality scale, as legal values come from
	 * configuration and vary per wiki.
	 *
	 * @param object $data Data structure to validate.
	 *
	 * @return StatusValue isOK if valid.
	 */
	protected function validateContentQualityScale( $data ) {
		// global $wgJadeContentQualityLevels;

		// foreach ( $data->proposals as $proposal ) {
		// foreach ( $proposal->schema as $schemaName => $value ) {
		// // Only validate the contentquality scale.
		// if ( $schemaName !== 'contentquality' ) {
		// continue;
		// }
	//
		// // Does this 1-based index appear in the locally-configured scale?
		// // TODO: Generalize this validation to reuse with other ordinal fields.
		// if ( !in_array( $value, range( 1, $wgJadeContentQualityLevels ) ) ) {
		// return Status::newFatal(
		// 'jade-bad-contentquality-value',
		// $value,
		// $wgJadeContentQualityLevels );
		// }
		// }
		// }
		return Status::newGood();
	}

	protected function validateEndorsementUsers( $data ) {
		// foreach ( $data->proposals as $proposal ) {
		// if ( !property_exists( $proposal, 'endorsements' ) ) {
		// // No endorsements, pass.
		// continue;
		// }
	//
		// foreach ( $proposal->endorsements as $endorsement ) {
		// if ( property_exists( $endorsement->user, 'ip' ) ) {
		// // Check that the IP address is a real thing.
		// if ( !IP::isValid( $endorsement->user->ip ) ) {
		// return Status::newFatal( 'jade-user-ip-invalid', $endorsement->user->ip );
		// }
		// }
	//
		// if ( property_exists( $endorsement->user, 'id' ) ) {
		// // Lookup by local user ID.
		// // TODO: Can be optimized by querying all users at once.
		// $localUsername = User::whoIs( intval( $endorsement->user->id ) );
		// if ( $localUsername === false ) {
		// // No such user.
		// return Status::newFatal( 'jade-user-local-id-invalid', $endorsement->user->id );
		// }
		// }
	//
		// if ( property_exists( $endorsement->user, 'cid' ) ) {
		// // Lookup by central user ID.
		// $localUser = CentralIdLookup::factory()->localUserFromCentralId(
		// intval( $endorsement->user->cid ),
		// CentralIdLookup::AUDIENCE_RAW );
		// if ( $localUser === null ) {
		// // No such user.
		// return Status::newFatal( 'jade-user-central-id-invalid', $endorsement->user->cid );
		// }
	//
		// // Check that the central and local users match.
		// if ( $localUser->getId() !== intval( $endorsement->user->id ) ) {
		// // IDs don't match.
		// return Status::newFatal(
		// 'jade-user-id-mismatch', $endorsement->user->id, $endorsement->user->cid );
		// }
		// }
		// }
		// }
		return Status::newGood();
	}

	protected function validateEndorsementTimestamps( $data ) {
		// foreach ( $data->proposals as $proposal ) {
		// if ( !property_exists( $proposal, 'endorsements' ) ) {
		// // No endorsements, pass.
		// continue;
		// }

		// foreach ( $proposal->endorsements as $endorsement ) {
		// if ( property_exists( $endorsement, 'created' ) ) {
		// $date = DateTime::createFromFormat( DateTime::ATOM, $endorsement->created );
		// if ( $date === false ) {
		// return Status::newFatal(
		// 'jade-created-timestamp-invalid', $endorsement->created );
		// }
		// }
		// }
		// }
		return Status::newGood();
	}

	/**
	 * Ensure that we're judging a real entity.
	 *
	 * @param string $type Entity type
	 * @param int $id Entity ID
	 *
	 * @return StatusValue results of validation.
	 */
	protected function validateEntity( $type, $id ) {
		switch ( $type ) {
			case 'diff':
			case 'revision':
				// Find Revision.
				$revision = $this->revisionStore->getRevisionById( $id );
				if ( $revision === null ) {
					return Status::newFatal( 'jade-bad-revision-id', $id );
				}
				break;
			default:
				// This is unreachable, but blow up just in case.
				return Status::newFatal( 'jade-bad-entity-type', $type );
		}
		return Status::newGood();
	}

	/**
	 * Ensure that the page title is allowed, the entity exists, and that
	 * proposal schemas match the entity type.
	 *
	 * @param WikiPage $page Page in which we're trying to store this proposal.
	 * @param object $proposal proposal data to validate against.
	 *
	 * @return StatusValue isOK if the page is valid, or fatal and the error message if invalid.
	 */
	public function validatePageTitle( WikiPage $page, $proposal ) {
		$title = $page->getTitle()->getTitleValue();

		$status = TitleHelper::parseTitleValue( $title );
		if ( !$status->isOK() ) {
			return $status;
		}
		$target = $status->value;

		// Assert that the title has the canonical form and, for example, no
		// leading zeroes.
		$expectedTitle = TitleHelper::buildJadeTitle( $target );
		if ( $expectedTitle->getDBkey() !== $title->getDBkey() ) {
			return Status::newFatal( 'jade-non-canonical-title', $expectedTitle->getDBkey() );
		}

		$status = $this->validateEntity( $target->entityType, $target->entityId );
		if ( !$status->isOK() ) {
			return $status;
		}
		return $this->validateEntitySchema( $target->entityType, $proposal );
	}

	/**
	 * Helper for comparing data against a JSON schema.  See
	 * http://json-schema.org
	 *
	 * @param object $data Data structure to validate.
	 * @param string $schemaPath Relative path to the schema we should use to
	 * validate.
	 *
	 * @return StatusValue isOK if valid.
	 */
	protected function validateAgainstSchema( $data, $schemaPath ) {
		$schemaDoc = (object)[ '$ref' => 'file://' . realpath( $schemaPath ) ];

		$validator = new Validator;
		try {
			$validator->validate(
				$data,
				$schemaDoc,
				Constraint::CHECK_MODE_EXCEPTIONS
			);
			return Status::newGood();
		} catch ( ValidationException $ex ) {
			// FIXME: English-only errors from justinrainbow!  Look into
			// EventLogging JSON validation for error i18n.
			return Status::newFatal( 'jade-bad-content', $ex->getMessage() );
		}
	}

}
