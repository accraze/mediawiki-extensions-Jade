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
namespace Jade\Tests;

use Jade\EntityLinkTableHelper;
use Jade\EntityType;
use MediaWikiTestCase;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Jade
 *
 * @coversDefaultClass \Jade\EntityLinkTableHelper
 */
class EntityLinkTableHelperTest extends MediaWikiTestCase {

	private $diffType;

	public function setUp() : void {
		parent::setUp();

		$this->diffType = EntityType::sanitizeEntityType( 'diff' )->value;
	}

	/**
	 * @covers ::__construct
	 */
	public function testNewFromEntityType() {
		$helper = new EntityLinkTableHelper( $this->diffType );
		$helper = TestingAccessWrapper::newFromObject( $helper );
		$this->assertEquals( $this->diffType, $helper->entityType );
	}

	/**
	 * @covers ::getLinkTable
	 */
	public function testGetLinkTable() {
		$helper = new EntityLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jade_diff_judgment', $helper->getLinkTable() );
	}

	/**
	 * @covers ::getColumnPrefix
	 */
	public function testGetColumnPrefix() {
		$helper = new EntityLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jaded', $helper->getColumnPrefix() );
	}

	/**
	 * @covers ::getIdColumn
	 */
	public function testGetIdColumn() {
		$helper = new EntityLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jaded_id', $helper->getIdColumn() );
	}

	/**
	 * @covers ::getProposalColumn
	 */
	public function testGetJudgmentColumn() {
		$helper = new EntityLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jaded_proposal', $helper->getProposalColumn() );
	}

	/**
	 * @covers ::getTargetColumn
	 */
	public function testGetTargetColumn() {
		$helper = new EntityLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jaded_revision', $helper->getTargetColumn() );
	}

	/**
	 * @covers ::getSummaryColumn
	 */
	public function testGetSummaryColumn() {
		$helper = new EntityLinkTableHelper( $this->diffType );
		$this->assertEquals( 'jaded_damaging', $helper->getSummaryColumn( 'damaging' ) );
	}

}
