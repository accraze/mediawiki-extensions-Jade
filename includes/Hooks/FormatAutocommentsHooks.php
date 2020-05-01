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

use Title;

class FormatAutocommentsHooks {

	/**
	 * Customize edit comments on history pages in the Jade namespace
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/FormatAutocomments
	 * @param string &$comment Reference to the accumulated comment.
	 * Initially null,when set the default code will be skipped.
	 * @param bool $pre Initial part of the parsed comment before the call to the hook.
	 * @param string $auto The extracted part of the parsed comment before the call to the hook.
	 * @param bool $post The final part of the parsed comment before the call to the hook.
	 * @param Title|null $title An optional title object used to links to sections. Can be null.
	 * @param bool $local Boolean indicating whether section links should refer to local page.
	 */
	public static function onFormatAutocomments( &$comment, $pre, $auto, $post, $title, $local ) {
		// phpcs:ignore MediaWiki.Usage.DeprecatedGlobalVariables.Deprecated$wgTitle
		global $wgTitle;
		if ( !( $title instanceof Title ) ) {
			$title = $wgTitle;
		}
		if ( !( $title instanceof Title ) ) {
			return;
		}
		if ( $title->inNamespace( NS_JADE ) ) {
			$autoComment = explode( "|", $auto, 2 );
			$autoCommentText = $autoComment[0];
			$autoCommentMsgKey = $autoCommentText . '-autocomment';
			$comment = wfMessage( $autoCommentMsgKey )->escaped();
		}
	}

}
