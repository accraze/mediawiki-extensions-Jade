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

use Content;
use Jade\Content\JudgmentContent;
use Status;
use StatusValue;

class JudgmentSummarizer {

	/**
	 * Extract preferred judgment values.
	 *
	 * @param Content $content Judgment page content to summarize.
	 * @return StatusValue When successful, includes a map from schema to preferred value.
	 */
	public static function getSummaryFromContent( Content $content ) {
		if ( JudgmentContent::CONTENT_MODEL_JUDGMENT !== $content->getModel() ) {
			return Status::newFatal( 'jade-content-model-error' );
		}
		// FIXME: Relies on duck typing, unfriendly to static analysis.
		$data = $content->getData()->getValue();

		$preferred = self::extractPreferredJudgment( $data );
		return Status::newGood( (array)$preferred->schema );
	}

	/**
	 * @param object $data Judgment page content as an object.
	 *
	 * @return object preferred judgment
	 */
	private static function extractPreferredJudgment( $data ) {
		$preferredList = array_filter(
			$data->judgments,
			function ( $judgment ) {
				return $judgment->preferred;
			}
		);
		return $preferredList[0];
	}

}
