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

namespace Jade;

/**
 * Pointer to wiki entity type and ID.
 */
class JudgmentTarget {

	// Only friend classes should be accessing these :-/
	/** @var JudgmentEntityType */
	public $entityType;
	/** @var int */
	public $entityId;

	/**
	 * Create a judgment target from type name and ID.
	 *
	 * @param JudgmentEntityType $entityType Name of wiki entity type, in lowercase.
	 * @param int $entityId Page ID or Revision ID of the entity.
	 */
	public function __construct( JudgmentEntityType $entityType, $entityId ) {
		$this->entityType = $entityType;
		$this->entityId = intval( $entityId );
	}

}
