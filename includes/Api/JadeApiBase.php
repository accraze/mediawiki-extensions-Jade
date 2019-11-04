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

use ApiBase;

/**
 * Base Api class to be inherited by all Jade Api modules.
 *
 * @license GPL-3.0-or-later
 * @author Andy Craze < acraze@wikimedia.org >
 */

abstract class JadeApiBase extends ApiBase {

	/**
	 * @see ApiBase::isWriteMode
	 * @return string
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * @see ApiBase::needsToken
	 *
	 * @return string
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::getHelpUrls
	 * @return string
	 */
	public function getHelpUrls() {
		return 'https://www.mediawiki.org/wiki/Extension:Jade';
	}

	private function checkErrors( $status ) {
		if ( is_string( $status ) ) {
			$this->dieWithError( $status );
		} else {
			$errors = $status->getErrors();
			if ( !empty( $errors ) ) {
				$this->dieStatus( $status );
			}
		}
	}

	private function checkWarnings( $warnings ) {
		if ( !is_null( $warnings ) ) {
			foreach ( $warnings as $warning ) {
				$this->addWarning( $warning );
			}
		}
	}

	public function buildResult( $data ) {
		list( $status, $entity, $warnings ) = $data;
		// @phan-suppress-next-line PhanUndeclaredClassMethod StatusValue
		$this->checkErrors( $status );
		$this->checkWarnings( $warnings );
		$res = $this->getResult();
		$res->addValue( null, "data", $entity );
	}

}
