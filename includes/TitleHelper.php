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

namespace Jade;

use Status;
use StatusValue;
use TitleValue;

class TitleHelper {

	/**
	 * Build Title object for the  page on an entity.
	 *
	 * @param EntityTarget $target Wiki entity to build a Jade entity page title for.
	 *
	 * @return TitleValue Path to where proposals about the given entity should
	 *         be stored.
	 */
	public static function buildJadeTitle( EntityTarget $target ) {
		// Get localized title component.
		$localTitle = $target->entityType->getLocalizedName();

		return new TitleValue(
			NS_JADE,
			"{$localTitle}/{$target->entityId}"
		);
	}

	/**
	 * Parse Entity Title object to get target wiki entity information.
	 *
	 * @param TitleValue $title proposal page title.
	 *
	 * @return StatusValue with EntityTarget value.
	 */
	public static function parseTitleValue( TitleValue $title ) {
		global $wgJadeEntityTypeNames;

		$namespace = $title->getNamespace();
		if ( $namespace !== NS_JADE ) {
			// This is not a proposal, fail.
			return Status::newFatal( 'jade-bad-title-namespace' );
		}
		$titleParts = explode( '/', $title->getDBkey() );
		if ( count( $titleParts ) !== 2 ) {
			return Status::newFatal( 'jade-bad-title-format' );
		}
		// Find localized title component and get type identifier.
		$typeName = array_search( $titleParts[0], $wgJadeEntityTypeNames, true );
		$status = EntityType::sanitizeEntityType( $typeName );
		if ( !$status->isOK() ) {
			return Status::newFatal( 'jade-bad-entity-type', $titleParts[0] );
		}
		$entityType = $status->value;
		$entityId = intval( $titleParts[1] );
		if ( $entityId === 0 ) {
			return Status::newFatal( 'jade-bad-entity-id-format', $titleParts[1] );
		}

		$target = new EntityTarget( $entityType, $entityId );
		return Status::newGood( $target );
	}

}
