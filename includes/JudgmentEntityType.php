<?php

namespace JADE;

use Status;
use StatusValue;

/**
 * Typesafe enum for entity type.
 */
class JudgmentEntityType {

	private $entityType;

	/**
	 * @param string $entityType Sanitized entity type identifier.
	 */
	private function __construct( $entityType ) {
		$this->entityType = $entityType;
	}

	/**
	 * @param string $entityType Name of wiki entity type, in lowercase.
	 * @return StatusValue Sanitized type if good.
	 */
	public static function sanitizeEntityType( $entityType ) {
		global $wgJadeEntityTypeNames;

		if ( !array_key_exists( (string)$entityType, $wgJadeEntityTypeNames ) ) {
			return Status::newFatal( 'jade-bad-entity-type', $entityType );
		}

		return Status::newGood( new self( $entityType ) );
	}

	/**
	 * Cast entity type to raw string.
	 */
	public function __toString() {
		return $this->entityType;
	}

	/**
	 * @return string Localized title component for this type.
	 */
	public function getLocalizedName() {
		global $wgJadeEntityTypeNames;

		return $wgJadeEntityTypeNames[$this->entityType];
	}

}
