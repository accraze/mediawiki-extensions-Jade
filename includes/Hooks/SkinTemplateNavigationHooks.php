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

class SkinTemplateNavigationHooks {

	/**
	 * Remove the "Edit" / "edit source" link from pages in the Jade namespace
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SkinTemplateNavigation
	 * @param \SkinTemplate $skinTemplate The skin template on which the UI is built.
	 * @param array &$links Navigation links.
	 */
	public static function onSkinTemplateNavigation( \SkinTemplate $skinTemplate, array &$links ) {
		if ( $skinTemplate->getTitle()->inNamespace( NS_JADE ) && isset( $links['views']['edit'] ) ) {
			unset( $links['views']['edit'] );
		}
	}

}
