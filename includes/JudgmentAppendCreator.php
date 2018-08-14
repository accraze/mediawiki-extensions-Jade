<?php

namespace JADE;

use MWException;

/**
 * Entry point used to create new judgments from MediaWiki core and extensions.
 *
 * Example:
 *
 *     use JADE\JADEServices;
 *     $creator = JADEServices::getAppendCreator();
 *     if ( $creator ) {
 *         $creator->createJudgment(
 *             'diff',
 *             123,
 *             'damaging',
 *             true,
 *             // Optional parameters
 *             'some reason or thought',
 *             'Patrolling for damage',
 *             ['RCP']
 *         );
 *     }
 */
class JudgmentAppendCreator {

	public function __construct( PageFormatter $formatter, EntityJudgmentSetStorage $store ) {
		$this->formatter = $formatter;
		$this->store = $store;
	}

	/**
	 * @param string $entityType Name of wiki entity type, in lowercase.
	 * @param int $entityId Page ID or Revision ID of the entity.
	 * @param string $schema Judgment schema name.
	 * @param mixed $data Raw data in a format described by the judgment schema.  Mandatory.
	 * @param string|null $notes Judgment notes, empty or null to omit.
	 * @param string|null $summary Edit summary, empty or null to omit.
	 * @param array|null $tags Change tags as an array of strings, or null.
	 *
	 * @throws MWException
	 * // TODO: @return
	 */
	public function createJudgment(
		$entityType,
		$entityId,
		$schema,
		$data,
		$notes = null,
		$summary = null,
		array $tags = null
	) {
		$judgment = $this->formatter->formatJudgment( $schema, $data, $notes );
		if ( $tags === null ) {
			$tags = [];
		}

		// Fetch any existing judgments.
		list( $page, $currentJudgment ) = $this->store->loadJudgmentSet( $entityType, $entityId );

		// Merge.
		$updatedJudgment = $this->formatter->unionPage( $currentJudgment, $judgment );

		// Store.
		$summary = $this->formatter->formatSummary( $schema, $summary );
		$this->store->storeJudgmentSet( $entityType, $entityId, $updatedJudgment, $summary, $tags );
	}

}
