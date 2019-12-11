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

namespace Jade\Api;

use Jade\EntityBuilder;
use RequestContext;

/**
 * API module that updates a users endorsement.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

class UpdateEndorsement extends JadeApiBase {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->requireOnlyOneParameter( $params, 'entitydata', 'title' );
		$builder = new EntityBuilder;
		$title = $builder->resolveTitle( $params );
		$contents = $builder->loadEntityPage( $title );
		if ( is_null( $contents ) ) {
			$this->dieWithError( 'jade-entitynotfound' );
		}
		$data = $builder->updateEndorsement( $params, $title, $contents );
		$this->buildResult( $data );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 * @return array
	 */
	protected function getAllowedParams() {
		$config = RequestContext::getMain()->getConfig();
		$allowedFacets = $config->get( 'JadeAllowedFacets' );
		return [
			'title' => [
				self::PARAM_TYPE => 'string',
			],
			'entitydata' => [
				self::PARAM_TYPE => 'text',
			],
			'facet' => [
				self::PARAM_TYPE => $allowedFacets,
				self::PARAM_REQUIRED => true,
			],
			'labeldata' => [
				self::PARAM_TYPE => 'text',
				self::PARAM_REQUIRED => true,
			],
			'user_id' => [
				self::PARAM_TYPE => 'integer',
			],
			'global_id' => [
				self::PARAM_TYPE => 'integer',
			],
			'ip' => [
				self::PARAM_TYPE => 'string',
			],
			'endorsementcomment' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			'comment' => [
				self::PARAM_TYPE => 'string',
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=jadeupdateendorsement&title=Jade:Diff/1234556&facet=editquality' .
			'&labeldata={"damaging": false, "goodfaith": true}' .
			'&endorsementcomment=this-is-an-update&formatversion=2'
				=> 'apihelp-jadeupdateendorsement-example',
			'action=jadeupdateendorsement&entitydata={"type": "diff", "id": "1234556"}' .
			'&facet=editquality&labeldata={"damaging": false, "goodfaith": true}' .
			'&endorsementcomment=this-is-an-update&formatversion=2'
				=> 'apihelp-jadeupdateendorsement-example'
		];
	}

}
