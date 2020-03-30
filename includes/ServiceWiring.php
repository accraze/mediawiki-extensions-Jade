<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

namespace Jade;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use RequestContext;

if ( !class_exists( ServiceWiring::class ) ) {
	class ServiceWiring {

		public static function getWiring() {
			return [

				'JadeEntityProposalSetStorage' => function ( MediaWikiServices $services ) {
					return new PageEntityProposalSetStorage();
				},

				'JadeEntityIndexStorage' => function ( MediaWikiServices $services ) {
					return new EntityLinkTable(
						$services->getDBLoadBalancer()
					);
				},

				'JadeProposalValidator' => function ( MediaWikiServices $services ) {
					return new ProposalValidator(
						RequestContext::getMain()->getConfig(),
						LoggerFactory::getInstance( 'Jade' ),
						$services->getRevisionStore()
					);
				},

			];
		}

	}
}

// @codeCoverageIgnoreStart
return ServiceWiring::getWiring();
// @codeCoverageIgnoreEnd
