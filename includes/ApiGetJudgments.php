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
use ApiPageSet;
use ApiQueryGeneratorBase;

/**
 *
 * @ingroup API
 */
class ApiGetJudgments extends ApiQueryGeneratorBase {

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName, 'gj' );
	}

	public function execute() {
		$this->run();
	}

	public function executeGenerator( $resultPageSet ) {
		$this->run( $resultPageSet );
	}

	/**
	 * @param ApiPageSet|null $resultPageSet
	 */
	private function run( $resultPageSet = null ) {
		$user = $this->getUser();
		$params = $this->extractRequestParams();
		$titles = [];

		$status = TitleHelper::buildJadeTitle( $params['entitytype'], $params['entityid'] );
		if ( !$status->isOK() ) {
			$this->dieStatus( $status );
		}
		$title = $status->value;
		if ( $title->exists() ) {
			$titles[] = $title;
		}

		if ( is_null( $resultPageSet ) ) {
			if ( count( $titles ) > 0 ) {
				$data = [];
				foreach ( $titles as $title ) {
					self::addTitleInfo( $data, $title );
				}
				$this->getResult()->addValue( [ 'query', $this->getModuleName() ], null, $data );
			}
			$this->getResult()->addIndexedTagName( [
				'query', $this->getModuleName()
			], 'judgment' );
		} else {
			$resultPageSet->populateFromTitles( $titles );
		}
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
		];
	}

	public function getCacheMode( $params ) {
		return 'public';
	}

	protected function getExamplesMessages() {
		return [
			'action=query&list=getjudgments&gjentitytype=diff&gjentityid=3'
				=> 'apihelp-query+jade-getjudgments-list-example',
			'action=query&generator=getjudgments&ggjentitytype=diff&ggjentityid=3&' .
			'prop=revisions&rvprop=content&rvslots=*'
				=> 'apihelp-query+jade-getjudgments-generator-example'
		];
	}

	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:JADE';
	}

}
