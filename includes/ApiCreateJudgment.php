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

namespace JADE;

use ApiBase;
use MWException;

/**
 *
 * @ingroup API
 */
class ApiCreateJudgment extends ApiBase {

	protected static $boolean_data = [ 'damaging', 'goodfaith' ];
	protected static $multi_data = [ 'drafttopic' ];

	public function execute() {
		$user = $this->getUser();
		$params = $this->extractRequestParams();
		$params = $this->coerceParams( $params );

		$creator = JADEServices::getAppendCreator();
		try {
			$result = $creator->createJudgment(
				$params['entitytype'],
				$params['entityid'],
				$params['schema'],
				$params['data'],
				$params['notes'],
				$params['summary'],
				$params['tags']
			);

			$this->getResult()->addValue( null, $this->getModuleName(), [ 'status' => 'Success' ] );
		} catch ( MWException $ex ) {
			$this->getResult()->addValue( null, $this->getModuleName(), [ 'status' => 'Fail' ] );
			$this->dieWithException( $ex );
		}
	}

	protected function coerceParams( $params ) {
		$data = $params['data'];

		// Un-multi manually :-/
		if ( !in_array( $params['schema'], self::$multi_data ) ) {
			$data = $data[0];
		}

		// Coerce booleans into a native form.  This has to be done explicitly
		// because ApiBase doesn't know how to determine the type of `data`.
		if ( in_array( $params['schema'], self::$boolean_data ) ) {
			if ( $data === 'false' || $data === '0' ) {
				$data = false;
			} elseif ( $data === 'true' || $data === '1' ) {
				$data = true;
			}
		}

		return [
			'data' => $data,
		] + $params;
	}

	public function getAllowedParams() {
		return [
			'entitytype' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			],
			'entityid' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_MIN => 1,
			],
			'schema' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true,
			],
			// Supports integer, string, or pipe-separated list of strings for
			// current schemas.
			'data' => [
				ApiBase::PARAM_TYPE => 'text',
				ApiBase::PARAM_ISMULTI => true,
				ApiBase::PARAM_DFLT => null,
				ApiBase::PARAM_ALLOW_DUPLICATES => false,
				ApiBase::PARAM_REQUIRED => true,
				ApiBase::PARAM_ISMULTI_LIMIT1 => 100,
				ApiBase::PARAM_ISMULTI_LIMIT2 => 1000,
			],
			'notes' => [
				ApiBase::PARAM_TYPE => 'text',
				ApiBase::PARAM_DFLT => '',
			],
			'summary' => [
				ApiBase::PARAM_TYPE => 'text',
				ApiBase::PARAM_DFLT => '',
			],
			'tags' => [
				ApiBase::PARAM_TYPE => 'tags',
				ApiBase::PARAM_ISMULTI => true,
			],
		];
	}

	protected function getExamplesMessages() {
		return [
			'action=createjudgment&entitytype=diff&entityid=3&schema=damaging&data=true&notes=just%20because'
				=> 'apihelp+jade-createjudgment-example'
		];
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:JADE';
	}

	public function isWriteMode() {
		return true;
	}

	public function needsToken() {
		return 'csrf';
	}

}
