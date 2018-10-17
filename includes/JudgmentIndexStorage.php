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

use WikiPage;

/**
 * Low-level service responsible for storing secondary indexes to judgments.
 */
interface JudgmentIndexStorage {

	/**
	 * Create any indexes needed to associate a judgment with its target.
	 *
	 * @param JudgmentTarget $target Wiki entity being judged.
	 * @param WikiPage $judgmentPage Page where judgment is recorded.
	 */
	public function insertIndex( JudgmentTarget $target, WikiPage $judgmentPage );

	/**
	 * Delete any indexes associating a judgment with its target.
	 *
	 * @param JudgmentTarget $target Wiki entity being judged.
	 * @param WikiPage $judgmentPage Page where judgment is recorded.
	 */
	public function deleteIndex( JudgmentTarget $target, WikiPage $judgmentPage );

}
