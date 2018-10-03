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

namespace JADE\Hooks;

use Status;
use Title;

class MoveHooks {

	/**
	 * @param Title $oldTitle object of the current (old) location
	 * @param Title $newTitle object of the new location
	 * @param Status $status object to pass error messages to
	 * FIXME: hook signature should be StatusValue
	 * TODO: Is there also a higher-level hook, which can disable the "Move"
	 * menu item?
	 */
	public static function onMovePageIsValidMove(
		Title $oldTitle,
		Title $newTitle,
		Status $status
	) {
		// Deny all moves within or into JudgmentPage.
		// TODO: In the future, we may allow some movements after validating.
		if ( $oldTitle->getNamespace() === NS_JUDGMENT
			|| $newTitle->getNamespace() === NS_JUDGMENT
		) {
			$status->error( 'jade-invalid-move-any' );
			$status->setOK( false );
			return;
		}
	}

}
