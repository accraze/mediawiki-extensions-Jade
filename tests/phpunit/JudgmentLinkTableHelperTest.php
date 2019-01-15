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
namespace JADE\Tests;

use JADE\JudgmentEntityType;
use JADE\JudgmentLinkTableHelper;
use MediaWikiTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group JADE
 *
 * @coversDefaultClass JADE\JudgmentLinkTableHelper
 */
class JudgmentLinkTableHelperTest extends MediaWikiTestCase {

	private $diffType;

	public function setUp() {
		parent::setUp();

		$this->diffType = JudgmentEntityType::sanitizeEntityType( 'diff' )->value;
	}

	/**
	 * @covers ::__construct
	 */
	public function testNewFromEntityType() {
		$helper = new JudgmentLinkTableHelper( $this->diffType );
		$helper = TestingAccessWrapper::newFromObject( $helper );
		$this->assertEquals( $this->diffType, $helper->entityType );
	}

	/**
	 * @covers ::getLinkTable
	 */
	public function testGetLinkTable() {
		$helper = new JudgmentLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jade_diff_judgment', $helper->getLinkTable() );
	}

	/**
	 * @covers ::getColumnPrefix
	 */
	public function testGetColumnPrefix() {
		$helper = new JudgmentLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jaded', $helper->getColumnPrefix() );
	}

	/**
	 * @covers ::getIdColumn
	 */
	public function testGetIdColumn() {
		$helper = new JudgmentLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jaded_id', $helper->getIdColumn() );
	}

	/**
	 * @covers ::getJudgmentColumn
	 */
	public function testGetJudgmentColumn() {
		$helper = new JudgmentLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jaded_judgment', $helper->getJudgmentColumn() );
	}

	/**
	 * @covers ::getTargetColumn
	 */
	public function testGetTargetColumn() {
		$helper = new JudgmentLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jaded_revision', $helper->getTargetColumn() );
	}

	/**
	 * @covers ::getSummaryColumn
	 */
	public function testGetSummaryColumn() {
		$helper = new JudgmentLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jaded_damaging', $helper->getSummaryColumn( 'damaging' ) );
	}

}
