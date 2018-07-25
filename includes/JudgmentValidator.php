<?php

namespace JADE;

use Config;
use InvalidArgumentException;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Exception\ValidationException;
use JsonSchema\Validator;
use MediaWiki\Storage\RevisionStore;
use Psr\Log\LoggerInterface;
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
	 * @return bool True if the content is valid.
	 */
	public function validateJudgmentContent( $data ) {
		try {
			$this->validateBasicSchema( $data );
			$this->validatePreferred( $data );

			return true;
		} catch ( ValidationException $ex ) {
			$this->logger->info(
				"Judgment doesn't conform to schema: {$ex->getMessage()}" );

			return false;
		} catch ( InvalidArgumentException $ex ) {
			$this->logger->info( "Invalid judgment: {$ex->getMessage()}" );

			return false;
		}
	}

	/**
	 * Ensure that the general judgment schema is followed.
	 *
	 * @throws ValidationException
	 *
	 * @param object $data Data structure to validate.
	 */
	protected function validateBasicSchema( $data ) {
		$this->validateAgainstSchema( $data, __DIR__ . self::JUDGMENT_SCHEMA );
	}

	/**
	 * Ensure that the score schemas are allowed by configuration.
	 *
	 * @throws ValidationException
	 * @throws InvalidArgumentException
	 *
	 * @param string $entityType Machine name for entity type.
	 * @param object $data Data structure to validate.
	 */
	protected function validateEntitySchema( $entityType, $data ) {
		$allowedScoringSchemas = $this->config->get( 'JadeAllowedScoringSchemas' );
		$entityAllowedSchemas = $allowedScoringSchemas[$entityType];

		foreach ( $data->schemas as $schemaName => $judgments ) {
			// Schema must be allowed.
			if ( !in_array( $schemaName, $entityAllowedSchemas ) ) {
				throw new InvalidArgumentException(
					"Scoring schema not allowed: {$schemaName}" );
			}
		}
	}

	/**
	 * Check that at most one judgment per schema has "preferred: true".
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param object $data Data structure to validate.
	 */
	protected function validatePreferred( $data ) {
		foreach ( $data->schemas as $schema => $judgments ) {
			$preferredCount = array_reduce(
				$judgments,
				function ( $carry, $judgment ) {
					$isPreferred = property_exists( $judgment, 'preferred' ) &&
						$judgment->preferred;

					return ( $carry + (int)$isPreferred );
				},
				0
			);

			if ( $preferredCount > 1 ) {
				throw new InvalidArgumentException(
					"Too many preferred judgments in {$schema}" );
			}
		}
	}

	/**
	 * Ensure that we're judging a real entity.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param string $type Entity type
	 * @param int $id Entity ID
	 */
	protected function validateEntity( $type, $id ) {
		if ( $type === 'diff' || $type === 'revision' ) {
			// Find Revision.
			$revision = $this->revisionStore->getRevisionById( $id );
			if ( $revision === null ) {
				throw new InvalidArgumentException(
					"Cannot find page by ID: {$id}" );
			}
		} elseif ( $type === 'page' ) {
			// Find Page.
			$page = WikiPage::newFromID( $id );
			if ( $page === null ) {
				throw new InvalidArgumentException(
					"Cannot find page by ID: {$id}" );
			}
		} else {
			// This is unreachable, but blow up just in case.
			throw new InvalidArgumentException(
				"Unknown entity type {$type}" );
		}
	}

	/**
	 * Ensure that the page title is consistent with the judgment content.
	 *
	 * @param WikiPage $page Page in which we're trying to store this judgment.
	 * @param object $judgment Judgment data to validate against.
	 *
	 * @return bool True if the page is valid.
	 */
	public function validatePageTitle( WikiPage $page, $judgment ) {
		try {
			$title = $page->getTitle();
			$titleText = $title->getDBkey();

			list( $type, $id ) = $this->parseAndValidateTitle( $titleText );

			$this->validateEntity( $type, $id );
			$this->validateEntitySchema( $type, $judgment );

			return true;
		} catch ( InvalidArgumentException $ex ) {
			$this->logger->info( "Invalid judgment page title: {$ex->getMessage()}" );

			return false;
		}
	}

	/**
	 * @param string $title Title that must match judgment.
	 *
	 * @return array [ machine name for entity type, entity id ]
	 *
	 * @throws InvalidArgumentException
	 */
	protected function parseAndValidateTitle( $title ) {
		global $wgJadeEntityTypeNames;

		$titleParts = explode( '/', $title );

		if ( count( $titleParts ) !== 2 ) {
			throw new InvalidArgumentException( "Wrong title format" );
		}
		list( $type, $id ) = $titleParts;

		$normalizedType = array_search( $type, $wgJadeEntityTypeNames, true );
		if ( $normalizedType === false ) {
			throw new InvalidArgumentException( "Bad entity type: {$type}" );
		}

		return [ $normalizedType, $id ];
	}

	/**
	 * Helper for comparing data against a JSON schema.  See
	 * http://json-schema.org
	 *
	 * @throws ValidationException
	 *
	 * @param object $data Data structure to validate.
	 * @param string $schemaPath Relative path to the schema we should use to
	 * validate.
	 */
	protected function validateAgainstSchema( $data, $schemaPath ) {
		$schemaDoc = (object)[ '$ref' => 'file://' . realpath( $schemaPath ) ];

		$validator = new Validator;
		$validator->validate(
			$data,
			$schemaDoc,
			Constraint::CHECK_MODE_EXCEPTIONS
		);
	}

}
