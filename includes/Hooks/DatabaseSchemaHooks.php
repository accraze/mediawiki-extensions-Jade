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

namespace Jade\Hooks;

use DatabaseUpdater;

class DatabaseSchemaHooks {

	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		$sqlDir = __DIR__ . '/../../sql';

		$updater->addExtensionTable(
			'jade_diff_judgment',
			$sqlDir . '/jade_diff_judgment.sql' );
		$updater->addExtensionTable(
			'jade_revision_judgment',
			$sqlDir . '/jade_revision_judgment.sql' );
	}

}
