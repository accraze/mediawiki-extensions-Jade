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
use TitleValue;

class TitleHelper {

	/**
	 * Build Title object for the judgment page on an entity.
	 *
	 * @param JudgmentTarget $target Wiki entity to build a judgment page title for.
	 *
	 * @return TitleValue Path to where judgments about the given entity should
	 *         be stored.
	 */
	public static function buildJadeTitle( JudgmentTarget $target ) {
		// Get localized title component.
		$localTitle = $target->entityType->getLocalizedName();

		return new TitleValue(
			NS_JUDGMENT,
			"{$localTitle}/{$target->entityId}"
		);
	}

	/**
	 * Parse Judgment Title object to get target wiki entity information.
	 *
	 * @param TitleValue $title Judgment page title.
	 *
	 * @return StatusValue with JudgmentTarget value.
	 */
	public static function parseTitle( TitleValue $title ) {
		global $wgJadeEntityTypeNames;

		$namespace = $title->getNamespace();
		if ( $namespace !== NS_JUDGMENT ) {
			// This is not a judgment, fail.
			return Status::newFatal( 'jade-bad-title-namespace' );
		}
		$titleParts = explode( '/', $title->getDBkey() );
		if ( count( $titleParts ) !== 2 ) {
			return Status::newFatal( 'jade-bad-title-format' );
		}
		// Find localized title component and get type identifier.
		$typeName = array_search( $titleParts[0], $wgJadeEntityTypeNames, true );
		$status = JudgmentEntityType::sanitizeEntityType( $typeName );
		if ( !$status->isOK() ) {
			return Status::newFatal( 'jade-bad-entity-type', $titleParts[0] );
		}
		$entityType = $status->value;
		$entityId = intval( $titleParts[1] );
		if ( $entityId === 0 ) {
			return Status::newFatal( 'jade-bad-entity-id-format', $titleParts[1] );
		}

		$target = new JudgmentTarget( $entityType, $entityId );
		return Status::newGood( $target );
	}

}
