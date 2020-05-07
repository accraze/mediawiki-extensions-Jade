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

use DOMDocument;
use DOMXPath;
use HistoryPager;

class PageHistoryLineEndingHooks {

	/**
	 * Customize edit comments on history pages in the Jade namespace
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageHistoryLineEnding
	 * @param HistoryPager $history the HistoryPager object
	 * @param object &$row the revision row for this line
	 * @param string &$line the HTML string representing this parsed line
	 * @param array &$classes CSS classes to apply
	 * @param array &$attribs associative array of other HTML attributes for the <li> element.
	 */
	public static function onPageHistoryLineEnding(
		HistoryPager $history,
		&$row,
		string &$line,
		array &$classes,
		array &$attribs
	) {
		if ( $history->getTitle()->inNamespace( NS_JADE ) ) {
			// load comment DOM and ensure UTF-8 is respected by using 'mb_convert_encoding'
			$commentDOM = new DOMDocument();
			// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
			@ $commentDOM->loadHTML( mb_convert_encoding( $line, 'HTML-ENTITIES', 'UTF-8' ) );
			// get elements by class name
			$commentXpath = new DOMXpath( $commentDOM );
			$spanWithCommentClass = $commentXpath->query( "//span[contains(@class,'comment')]" );
			// replace old with new comment text and preserve the HTML stripped out by DOMXpath
			// see https://www.php.net/manual/en/class.domelement.php#86803
			foreach ( $spanWithCommentClass as $htmlTagInSpan ) {
				$innerHTML = '';
				$children = $htmlTagInSpan->childNodes;
				foreach ( $children as $child ) {
					$tmp_doc = new DOMDocument();
					$tmp_doc->appendChild( $tmp_doc->importNode( $child, true ) );
					// customize comment text
					$oldTextToRemove = [
						'editquality is',
						'{"damaging":false,"goodfaith":true}',
						'{"damaging":true,"goodfaith":true}',
						'{"damaging":true,"goodfaith":false}'
					];
					$newTextToAdd = [
						wfMessage( 'jade-editquality-is-comment' )->escaped(),
						wfMessage( 'jade-productive-good-faith-comment' )->escaped(),
						wfMessage( 'jade-damaging-good-faith-comment' )->escaped(),
						wfMessage( 'jade-damaging-bad-faith-comment' )->escaped()
					];
					$child->nodeValue = str_replace(
						$oldTextToRemove,
						$newTextToAdd,
						$child->nodeValue
					);
					$innerHTML .= $tmp_doc->saveHTML();
				}
			}
			// save new comment line
			$line = $commentDOM->saveHTML();
		}
	}

}
