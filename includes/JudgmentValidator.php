<?php

namespace JADE;

use Config;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Exception\ValidationException;
use JsonSchema\Validator;
use MediaWiki\Storage\RevisionStore;
use Psr\Log\LoggerInterface;
use Status;
use StatusValue;
use WikiPage;

class JudgmentValidator {
	const JUDGMENT_SCHEMA = '/../jsonschema/judgment/v1.json';
	const SCORING_SCHEMA_ROOT = '/../jsonschema/scoring';

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

	public function __construct( $config, $logger, $revisionStore ) {
		$this->config = $config;
		$this->logger = $logger;
		$this->revisionStore = $revisionStore;
	}

	/**
	 * Check that the judgment content is well-formed.
	 *
	 * @param object $data Data structure to validate.
	 *
	 * @return StatusValue isOK if the content is valid.
	 */
	public function validateJudgmentContent( $data ) {
		$status = $this->validateBasicSchema( $data );
		if ( !$status->isOK() ) {
			return $status;
		}
		return $this->validatePreferred( $data );
	}

	/**
	 * Ensure that the general judgment schema is followed.
	 *
	 * @param object $data Data structure to validate.
	 *
	 * @return StatusValue isOK?
	 */
	protected function validateBasicSchema( $data ) {
		return $this->validateAgainstSchema( $data, __DIR__ . self::JUDGMENT_SCHEMA );
	}

	/**
	 * Ensure that the score schemas are allowed by configuration.
	 *
	 * @param string $entityType Machine name for entity type.
	 * @param object $data Data structure to validate.
	 *
	 * @return StatusValue isOK if valid.
	 */
	protected function validateEntitySchema( $entityType, $data ) {
		$allowedScoringSchemas = $this->config->get( 'JadeAllowedScoringSchemas' );
		$entityAllowedSchemas = $allowedScoringSchemas[$entityType];

		foreach ( $data->judgments as $judgment ) {
			foreach ( $judgment->schema as $schemaName => $value ) {
				// Schema must be allowed.
				if ( !in_array( $schemaName, $entityAllowedSchemas ) ) {
					return Status::newFatal( 'jade-illegal-schema', $schemaName );
				}
			}
		}
		return Status::newGood();
	}

	/**
	 * Check that exactly one judgment is preferred.
	 *
	 * @param object $data Data structure to validate.
	 *
	 * @return StatusValue isOK if valid.
	 */
	protected function validatePreferred( $data ) {
		$preferredCount = 0;
		foreach ( $data->judgments as $judgment ) {
			if ( $judgment->preferred ?? false ) {
				$preferredCount++;
			}
		}
		if ( $preferredCount < 1 ) {
			return Status::newFatal( 'jade-none-preferred' );
		}
		if ( $preferredCount > 1 ) {
			return Status::newFatal( 'jade-too-many-preferred' );
		}

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
	 * judgment schemas match the entity type.
	 *
	 * @param WikiPage $page Page in which we're trying to store this judgment.
	 * @param object $judgment Judgment data to validate against.
	 *
	 * @return StatusValue isOK if the page is valid, or fatal and the error message if invalid.
	 */
	public function validatePageTitle( WikiPage $page, $judgment ) {
		$title = $page->getTitle();
		$titleText = $title->getDBkey();

		$status = $this->parseAndValidateTitle( $titleText );
		if ( !$status->isOK() ) {
			return $status;
		}
		$type = $status->value['entityType'];
		$id = $status->value['entityId'];

		$status = $this->validateEntity( $type, $id );
		if ( !$status->isOK() ) {
			return $status;
		}
		return $this->validateEntitySchema( $type, $judgment );
	}

	/**
	 * @param string $title Title that must match judgment.
	 *
	 * @return StatusValue $out->value is an array with keys `entityType` and `entityId`.
	 */
	protected function parseAndValidateTitle( $title ) {
		global $wgJadeEntityTypeNames;

		$titleParts = explode( '/', $title );

		if ( count( $titleParts ) !== 2 ) {
			return Status::newFatal( 'jade-bad-title-format' );
		}
		list( $type, $id ) = $titleParts;

		$normalizedType = array_search( $type, $wgJadeEntityTypeNames, true );
		if ( $normalizedType === false ) {
			return Status::newFatal( 'jade-bad-entity-type', $type );
		}

		return Status::newGood( [
			'entityType' => $normalizedType,
			'entityId' => $id,
		] );
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
