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
 * API module to create a new proposal and file an endorsement.
 * If the user already endorsed another proposal within the facet,
 * move the user's endorsement to the new proposal.
 *
 * @ingroup API
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

class CreateAndEndorse extends JadeApiBase {

	/**
	 * @see ApiBase::execute
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$this->requireOnlyOneParameter( $params, 'entitydata', 'title' );
		$builder = new EntityBuilder( $this->getUser() );
		$title = $builder->resolveTitle( $params, true );
		$data = $builder->createAndEndorse( $params, $title );
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
			'notes' => [
				self::PARAM_TYPE => 'string',
			],
			'endorsementcomment' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_DFLT => 'As proposer',
			],
			'endorsementorigin' => [
				self::PARAM_TYPE => 'string',
				self::PARAM_REQUIRED => true,
			],
			'nomove' => [
				self::PARAM_TYPE => 'boolean',
				self::PARAM_DFLT => false,
			],
			'comment' => [
				self::PARAM_TYPE => 'text',
			],
		];
	}

	/**
	 * @see ApiBase::getExamplesMessages
	 * @return array
	 */
	protected function getExamplesMessages() {
		return [
			'action=jadecreateandendorse&title=Jade:Diff/1234556&facet=editquality' .
			'&labeldata={"damaging":false,%20"goodfaith":true}&notes=this-makes-more-sense' .
			'&endorsementorigin=mw-api-sandbox&comment=this-is-a-test&formatversion=2'
				=> 'apihelp-jadecreateandendorse-example',
			'action=jadecreateandendorse&entitydata={"type":"diff",%20"id":"1234556"}' .
			'&facet=editquality&labeldata={"damaging": false, "goodfaith": true}' .
			'&notes=this-makes-more-sense&endorsementorigin=mwapi-sandbox&formatversion=2'
				=> 'apihelp-jadecreateandendorse-example-2',
		];
	}
}
