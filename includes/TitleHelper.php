<?php
namespace JADE;

use MWException;
use Title;

class TitleHelper {

	/**
	 * @param string $entityType Machine name of entity type.
	 * @param int $entityId Entity ID.
	 *
	 * @return Title Where to find a judgment about the given entity.
	 *
	 * @throws MWException
	 */
	public static function buildJadeTitle( $entityType, $entityId ) {
		global $wgJadeEntityTypeNames;

		$entityType = strtolower( $entityType );
		// Get localized title component.
		if ( !array_key_exists( $entityType, $wgJadeEntityTypeNames ) ) {
			throw new MWException( "Invalid entity type: {$entityType}" );
		}
		$localTitle = $wgJadeEntityTypeNames[$entityType];

		return Title::makeTitle(
			NS_JUDGMENT,
			"{$localTitle}/{$entityId}"
		);
	}

}
