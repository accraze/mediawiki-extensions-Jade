<?php

namespace JADE;

interface EntityJudgmentSetStorage {

	/**
	 * Store a revision of judgment content.
	 *
	 * Overwrites the page without merging.
	 *
	 * TODO: editRevId for conflict detection?
	 * @param string $entityType Name of wiki entity type, in lowercase.
	 * @param int $entityId Page ID or Revision ID of the entity.
	 * @param array $judgmentSet All judgments on this entity, as nested
	 * associative arrays, normalized for storage.
	 * @param string $summary Edit summary.
	 * @param array $tags Optional list of change tags to set on the revision being created.
	 *
	 * @return StatusValue isOK if stored successfully.
	 */
	public function storeJudgmentSet( $entityType, $entityId, $judgmentSet, $summary, $tags );

	/**
	 * @param string $entityType Name of wiki entity type, in lowercase.
	 * @param int $entityId Page ID or Revision ID of the entity.
	 *
	 * @return StatusValue with array value containing all judgments for this
	 *         entity.
	 */
	public function loadJudgmentSet( $entityType, $entityId );

}
