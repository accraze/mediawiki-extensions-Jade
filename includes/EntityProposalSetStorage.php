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

use StatusValue;
use User;

interface EntityProposalSetStorage {

	/**
	 * Store a revision of proposal content.
	 *
	 * Overwrites the page without merging.
	 *
	 * TODO: editRevId for conflict detection?
	 * @param ProposalTarget $target identity of target wiki entity.
	 * @param array $proposalSet All proposals on this entity, as nested
	 * associative arrays, normalized for storage.
	 * @param string $summary Edit summary.
	 * @param User $user User to attribute to
	 * @param array $tags Optional list of change tags to set on the revision being created.
	 *
	 * @return StatusValue isOK if stored successfully.
	 */
	public function storeProposalSet(
		ProposalTarget $target,
		array $proposalSet,
		$summary,
		User $user,
		array $tags );

	/**
	 * @param ProposalTarget $target identity of target wiki entity.
	 *
	 * @return StatusValue with array value containing all proposals for this
	 *         entity.
	 */
	public function loadProposalSet( ProposalTarget $target );

}
