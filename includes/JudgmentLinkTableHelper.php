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

class JudgmentLinkTableHelper {

	public static function getLinkTable( $entityType ) {
		// Dynamic link table name per entity type, e.g. "jade_diff_judgment".
		return "jade_{$entityType}_judgment";
	}

	public static function getColumnPrefix( $entityType ) {
		// Table column prefix is constructed using the first letter of the entity type name.
		return "jade{$entityType[0]}";
	}

	public static function getIdColumn( $entityType ) {
		$columnPrefix = self::getColumnPrefix( $entityType );
		return "{$columnPrefix}_id";
	}

	public static function getJudgmentColumn( $entityType ) {
		$columnPrefix = self::getColumnPrefix( $entityType );
		// Column linking to judgment pages, e.g. "jaded_judgment" for the diff link table.
		return "{$columnPrefix}_judgment";
	}

	public static function getTargetColumn( $entityType ) {
		$columnPrefix = self::getColumnPrefix( $entityType );
		// Column linking to judgment target revisions, e.g. "jaded_revision".
		return "{$columnPrefix}_revision";
	}

}
