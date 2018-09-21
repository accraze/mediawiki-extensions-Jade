<?php
namespace JADE;

use Status;
use StatusValue;
use Title;

class TitleHelper {

	/**
	 * Build Title object for the judgment page on an entity.
	 *
	 * @param string $entityType Machine name of entity type.
	 * @param int $entityId Entity ID.
	 *
	 * @return StatusValue value set to a Title where the judgment about the
	 *         given entity can be stored.
	 */
	public static function buildJadeTitle( $entityType, $entityId ) {
		global $wgJadeEntityTypeNames;

		$entityType = strtolower( $entityType );
		// Get localized title component.
		if ( !array_key_exists( $entityType, $wgJadeEntityTypeNames ) ) {
			return Status::newFatal( 'jade-bad-entity-type', $entityType );
		}
		$localTitle = $wgJadeEntityTypeNames[$entityType];

		$title = Title::makeTitle(
			NS_JUDGMENT,
			"{$localTitle}/{$entityId}"
		);
		return Status::newGood( $title );
	}

}
