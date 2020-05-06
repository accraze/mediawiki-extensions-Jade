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

class EntityLinkTableHelper {

	/** @var EntityType */
	private $entityType;

	/**
	 * @param EntityType $entityType Link table will be for this type.
	 */
	public function __construct( EntityType $entityType ) {
		$this->entityType = $entityType;
	}

	public function getLinkTable() {
		// Dynamic link table name per entity type, e.g. "jade_diff_label".
		return "jade_{$this->entityType}_label";
	}

	public function getColumnPrefix() {
		// Table column prefix is constructed using the first letter of the entity type name.
		$entityTypeString = (string)$this->entityType;
		$prefix = '';
		if ( $entityTypeString == 'diff' ) {
			$prefix = 'dl';
		} elseif ( $entityTypeString == 'revision' ) {
			$prefix = 'rl';
		}
		return "jade{$prefix}";
	}

	public function getIdColumn() {
		$columnPrefix = $this->getColumnPrefix();
		return "{$columnPrefix}_id";
	}

	public function getPageColumn() {
		$columnPrefix = $this->getColumnPrefix();
		// Column linking to jade entity pages, e.g. "jadedl_page_id" for the diff link table.
		return "{$columnPrefix}_page_id";
	}

	public function getTargetColumn() {
		$columnPrefix = $this->getColumnPrefix();
		// Column linking to proposal target revisions, e.g. "jadedl_rev_id".
		return "{$columnPrefix}_rev_id";
	}

	public function getSummaryColumn( $schema ) {
		$columnPrefix = $this->getColumnPrefix();
		return "{$columnPrefix}_{$schema}";
	}

}
