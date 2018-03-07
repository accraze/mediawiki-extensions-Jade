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
			// Validate against the judgment schema.
			$this->validateBasicSchema( $data );

			// Validate that scoring schema are allowed and unique.  Enforce
			// them on the score data.
			$this->validateScoreSchemas( $data );

			// Make sure we're targeting a valid entity.
			$this->validateEntity( $data );

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
	 * Ensure that the score schemas are allowed, unique, and that the score
	 * data follows the scoring schema.
	 *
	 * @throws ValidationException
	 * @throws InvalidArgumentException
	 *
	 * @param object $data Data structure to validate.
	 */
	protected function validateScoreSchemas( $data ) {
		$entityType = $data->entity->type;
		$allowedScoringSchemas = $this->config->get( 'JadeAllowedScoringSchemas' );
		$entityAllowedSchemas = $allowedScoringSchemas[$entityType];

		$bySchema = [];
		foreach ( $data->scores as $score ) {
			$schemaName = $score->schema->name;

			// Schema must be allowed.
			if ( !array_key_exists( $schemaName, $entityAllowedSchemas ) ) {
				throw new InvalidArgumentException(
					"Scoring schema not allowed: {$schemaName}" );
			}

			// Only one score allowed per schema.
			if ( isset( $bySchema[$schemaName] ) ) {
				throw new InvalidArgumentException(
					"Redundant scoring schema: ${schemaName}" );
			}
			$bySchema[$schemaName] = true;

			// Validate score data against its schema.  Note that we ignore the
			// $score["schema"]["spec"].
			$schemaPath = __DIR__ . self::SCORING_SCHEMA_ROOT
				. '/' . $entityAllowedSchemas[$schemaName];
			$this->validateAgainstSchema( $score->data, $schemaPath );
		}
	}

	/**
	 * Ensure that we're judging an existent entity.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param object $data Data structure to validate.
	 */
	protected function validateEntity( $data ) {
		$entity = $data->entity;

		if ( $entity->type === 'diff' || $entity->type === 'revision' ) {
			// Find Revision.
			$revision = $this->revisionStore->getRevisionById( $entity->rev_id );
			if ( $revision === null ) {
				throw new InvalidArgumentException(
					"Cannot find page by ID: {$entity->rev_id}" );
			}
		} elseif ( $entity->type === 'page' ) {
			// Find Page.
			$page = WikiPage::newFromID( $entity->page_id );
			if ( $page === null ) {
				throw new InvalidArgumentException(
					"Cannot find page by ID: {$entity->page_id}" );
			}
		} else {
			// This is unreachable, but blow up just in case.
			throw new InvalidArgumentException(
				"Unknown entity type {$entity->type}" );
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
			$this->validateTitle(
				$page->getTitle()->getDBkey(),
				$judgment
			);

			return true;
		} catch ( InvalidArgumentException $ex ) {
			$this->logger->info( "Invalid judgment page title: {$ex->getMessage()}" );

			return false;
		}
	}

	/**
	 * @param string $title Title that must match judgment.
	 * @param object $judgment Judgment data to validate against.
	 *
	 * @throws InvalidArgumentException
	 */
	protected function validateTitle( $title, $judgment ) {
		$title_parts = explode( '/', $title );
		if ( count( $title_parts ) !== 2 ) {
			throw new InvalidArgumentException( "Wrong title format" );
		}
		list( $type, $id ) = $title_parts;

		$judgment_entity_type = $judgment->entity->type;

		if ( ucfirst( $judgment_entity_type ) !== $type ) {
			throw new InvalidArgumentException(
				"Judgment type doesn't match title"
			);
		}

		if ( $judgment_entity_type === 'diff'
			|| $judgment_entity_type === 'revision'
		) {
			if ( (string)$judgment->entity->rev_id !== $id ) {
				throw new InvalidArgumentException(
					"Judgment rev_id doesn't match title"
				);
			}
		} else {
			if ( (string)$judgment->entity->page_id !== $id ) {
				throw new InvalidArgumentException(
					"Judgment page_id doesn't match title"
				);
			}
		}
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
