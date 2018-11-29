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

namespace Jade\Content;

use JsonContentHandler;
use ParserOutput;
use Sanitizer;
use SearchEngine;
use Title;
use WikiPage;

class JudgmentContentHandler extends JsonContentHandler {

	public function __construct( $modelId = JudgmentContent::CONTENT_MODEL_JUDGMENT ) {
		parent::__construct( $modelId );
	}

	protected function getContentClass() {
		return JudgmentContent::class;
	}

	public function canBeUsedOn( Title $title ) {
		return $title->inNamespace( NS_JUDGMENT );
	}

	/**
	 * Rewrite the text that will show up in the search results summary, as its
	 * rendered form rather than raw JSON.
	 *
	 * TODO: This is where we would populate additional indexes into the data
	 * itself.
	 *
	 * @param WikiPage $page Page to index
	 * @param ParserOutput $output Rendered page
	 * @param SearchEngine $engine Search engine for which we are indexing
	 *
	 * @return array Map of name=>value for fields
	 */
	public function getDataForSearchIndex(
		WikiPage $page,
		ParserOutput $output,
		SearchEngine $engine
	) {
		$fields = parent::getDataForSearchIndex( $page, $output, $engine );

		$text = $output->getText();
		$stripped = trim( Sanitizer::stripAllTags( $text ) );

		$fields['text'] = $stripped;

		return $fields;
	}

}
