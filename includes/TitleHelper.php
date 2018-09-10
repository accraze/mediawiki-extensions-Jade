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
