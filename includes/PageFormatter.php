<?php

namespace JADE;

/**
 * Format entity judgments in the entity judgment page structure.
 */
class PageFormatter {

	/**
	 * Express a single judgment in the page content schema.
	 *
	 * @param string $schema Judgment schema name.
	 * @param mixed $data Raw data in a format described by the judgment schema.  Mandatory.
	 * @param string|null $notes Judgment notes, empty or null to omit.
	 *
	 * @return array Data form of judgment.
	 */
	public function formatJudgment( $schema, $data, $notes ) {
		// Validation is done by the content handler during doeditcontent, so we
		// can skip it here.
		$newItem = [
			'data' => $data,
		];
		if ( $notes !== null && $notes !== '' ) {
			$newItem['notes'] = $notes;
		}

		return [
			'schemas' => [
				$schema => [ $newItem ],
			],
		];
	}

	/**
	 * Create or modify summary field to include "section" info.
	 *
	 * FIXME: Should be a different formatter's responsibility.
	 *
	 * @param string $schema Judgment schema name.
	 * @param string $summary Upstream summary.
	 *
	 * @return string New edit summary.
	 */
	public function formatSummary( $schema, $summary ) {
		$prefix = "/* add {$schema} judgment */";
		if ( $summary === null || $summary === '' ) {
			return $prefix;
		}
		return "{$prefix} {$summary}";
	}

	/**
	 * Merge two entity judgment pages.  Judgments lists will be concatenated.
	 *
	 * @param array $left Array to be updated.
	 * @param array $right Higher-priority elements.
	 *
	 * @return array Merged page including both inputs.
	 */
	public function unionPage( $left, $right ) {
		// Happens to have the behavior we want: array items are concatenated.
		return array_merge_recursive( $left, $right );
	}

}
