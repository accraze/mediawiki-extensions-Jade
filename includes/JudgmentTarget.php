<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace JADE;

use InvalidArgumentException;

/**
 * Pointer to wiki entity type and ID.
 */
class JudgmentTarget {

	// Only friend classes should be accessing these :-/
	public $entityType;
	public $entityId;

	/**
	 * Create a judgment target from type name and ID.
	 *
	 * @param string $entityType Name of wiki entity type, in lowercase.
	 * @param int $entityId Page ID or Revision ID of the entity.
	 *
	 * @return JudgmentTarget new target object.
	 * @throws InvalidArgumentException when entity type is invalid.
	 */
	public static function newGeneric( $entityType, $entityId ) {
		global $wgJadeEntityTypeNames;

		$target = new JudgmentTarget;
		// Untrusted parameters should never reach this code, but assert valid
		// parameters to prevent SQL injection regardless.
		if ( !array_key_exists( $entityType, $wgJadeEntityTypeNames ) ) {
			throw new InvalidArgumentException( "Unsupported judgment entity type '{$entityType}'" );
		}
		$target->entityType = $entityType;
		$target->entityId = intval( $entityId );
		return $target;
	}

}
