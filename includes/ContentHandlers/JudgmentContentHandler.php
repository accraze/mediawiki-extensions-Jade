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

namespace JADE\ContentHandlers;

use JsonContentHandler;
use Title;

class JudgmentContentHandler extends JsonContentHandler {

	public function __construct( $modelId = 'JadeJudgment' ) {
		parent::__construct( $modelId );
	}

	protected function getContentClass() {
		return JudgmentContent::class;
	}

	public function canBeUsedOn( Title $title ) {
		return $title->inNamespace( NS_JUDGMENT );
	}

}
