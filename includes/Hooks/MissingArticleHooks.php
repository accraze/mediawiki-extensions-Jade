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

use Article;
use OutputPage;
use RequestContext;

class MissingArticleHooks {

	/**
	 * Used to build an empty jade entity form if article does not exist
	 * @see   https://www.mediawiki.org/wiki/Manual:Hooks/ShowMissingArticle
	 * @param Article $article
	 * @return void
	 */
	public static function onShowMissingArticle( Article $article ) {
		if ( $article->getTitle()->getNamespace() === NS_JADE ) {
			$context = RequestContext::getMain();
			$output = $context->getOutput();
			OutputPage::setupOOUI();
			$entityData = json_encode( json_decode( '{}' ) );
			$jsConfigVars = [
				'entityData' => $entityData,
				'entityTitle' => $article->getTitle(),
			];
			$output->addJsConfigVars( $jsConfigVars );
			$output->addModules( [ 'ext.Jade.entityView', 'jade.api','jade.widgets', 'jade.dialogs' ] );
		}
	}
}
