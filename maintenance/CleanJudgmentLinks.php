<?php

namespace Jade\Maintenance;

use Jade\JadeServices;
use Jade\ProposalEntityType;
use Jade\ProposalLinkTableHelper;
use Jade\ProposalSummarizer;
use Jade\TitleHelper;
use Maintenance;
use MediaWiki\MediaWikiServices;
use Title;
use Wikimedia\Rdbms\IResultWrapper;
use WikiPage;

// @codeCoverageIgnoreStart
require_once getenv( 'MW_INSTALL_PATH' ) !== false
	? getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php'
	: __DIR__ . '/../../../maintenance/Maintenance.php';
// @codeCoverageIgnoreEnd

/**
 * Script to enforce data integrity on the judgment link tables.
 *
 * @ingroup Maintenance
 *
 * TODO: We're breaking the JudgmentIndexStorage abstraction here.
 * Database operations could be extracted to a maintenance helper class
 * instead.  However, I'm not sure yet whether an alternative storage backend
 * would have the same concerns, so leaving to future work.
 */
class CleanJudgmentLinks extends Maintenance {

	const DEFAULT_SQL_SELECT_SIZE = 1000;

	public function __construct() {
		parent::__construct();

		$this->requireExtension( 'Jade' );
		$this->addDescription( 'Clean up judgment link tables, looking for orphaned ' .
			'and missing links.' );
		$this->addOption( 'dry-run', 'Search but don\'t make changes to the link tables.' );

		$this->setBatchSize( self::DEFAULT_SQL_SELECT_SIZE );
	}

	public function execute() {
		$this->output( "Starting Jade cleanup...\n" );

		$this->findAndDeleteOrphanedLinks();
		$this->findAndConnectUnlinkedJudgments();

		$this->output( "Done.\n" );
	}

	private function findAndDeleteOrphanedLinks() {
		global $wgJadeEntityTypeNames;
		$entityTypes = array_keys( $wgJadeEntityTypeNames );

		foreach ( $entityTypes as $type ) {
			$skipPastId = 0;
			$status = ProposalEntityType::sanitizeEntityType( $type );
			$entityType = $status->value;

			do {
				$orphans = $this->findOrphanedLinks( $entityType, $skipPastId );
				if ( $orphans ) {
					$this->deleteOrphanedLinks( $orphans, $entityType );
					$skipPastId = $orphans[count( $orphans ) - 1];
				}
			} while ( count( $orphans ) );
		}
	}

	/**
	 * Find link entries for which the judgment page is missing.
	 *
	 * @param ProposalEntityType $type Entity type for this batch.
	 * @param int $skipPastId Search beginning with this primary key value.
	 *
	 * @return array List of primary keys for orphaned link table rows.
	 */
	private function findOrphanedLinks( $type, $skipPastId = 0 ) {
		$tableHelper = new ProposalLinkTableHelper( $type );

		$dbr = MediaWikiServices::getInstance()
			->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$orphans = $dbr->selectFieldValues(
			[
				$tableHelper->getLinkTable(),
				'page',
			],
			$tableHelper->getIdColumn(),
			[
				'page_id' => null,
				"{$tableHelper->getIdColumn()} > {$skipPastId}",
			],
			__METHOD__,
			[
				'LIMIT' => $this->getBatchSize(),
				'ORDER BY' => $tableHelper->getIdColumn(),
			],
			[ 'page' => [
				'LEFT JOIN', "page_id = {$tableHelper->getProposalColumn()}",
			] ]
		);
		return $orphans;
	}

	/**
	 * Bulk delete link rows.
	 *
	 * @param array $orphans List of primary keys to link rows.
	 * @param ProposalEntityType $type Entity type
	 */
	private function deleteOrphanedLinks( $orphans, $type ) {
		$tableHelper = new ProposalLinkTableHelper( $type );

		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$dbw = $lbFactory->getMainLB()->getConnection( DB_MASTER );
		if ( !$this->getOption( 'dry-run' ) ) {
			$dbw->delete(
				$tableHelper->getLinkTable(),
				[ $tableHelper->getIdColumn() => $orphans ],
				__METHOD__
			);
			$lbFactory->waitForReplication();
		}

		$numDeleted = count( $orphans );
		$message = "Deleted {$numDeleted} orphaned {$type} links.";
		if ( $this->getOption( 'dry-run' ) ) {
			$message .= ' (dry run)';
		}
		$this->output( "{$message}\n" );
	}

	private function findAndConnectUnlinkedJudgments() {
		global $wgJadeEntityTypeNames;
		$entityTypes = array_keys( $wgJadeEntityTypeNames );

		foreach ( $entityTypes as $type ) {
			$skipPastId = 0;
			do {
				$status = ProposalEntityType::sanitizeEntityType( $type );
				$entityType = $status->value;

				$unlinked = $this->findUnlinkedJudgments( $entityType, $skipPastId );
				if ( $unlinked->numRows() > 0 ) {
					$skipPastId = $this->connectUnlinkedJudgments( $unlinked, $entityType );
				}
			} while ( $unlinked->numRows() > 0 );
		}
	}

	private function findUnlinkedJudgments( ProposalEntityType $type, $skipPastId ) {
		$tableHelper = new ProposalLinkTableHelper( $type );
		$titlePrefix = $type->getLocalizedName();

		// Find judgments with no matching link row.
		$dbr = MediaWikiServices::getInstance()
			->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$unlinked = $dbr->select(
			[
				$tableHelper->getLinkTable(),
				'page',
			],
			[ 'page_id', 'page_namespace', 'page_title', $tableHelper->getIdColumn() ],
			[
				'page_namespace = ' . intval( NS_JADE ),
				'page_title ' . $dbr->buildLike( "{$titlePrefix}/", $dbr->anyString() ),
				"page_id > {$skipPastId}",
				$tableHelper->getProposalColumn() => null,
			],
			__METHOD__,
			[
				'LIMIT' => $this->getBatchSize(),
				'ORDER BY' => $tableHelper->getIdColumn(),
			],
			[ $tableHelper->getLinkTable() => [
				'LEFT JOIN', "page_id = {$tableHelper->getProposalColumn()}",
			] ]
		);

		return $unlinked;
	}

	/**
	 * Helper to make new links for a list of judgment pages.
	 *
	 * @param IResultWrapper $unlinked judgment pages to reconnect.
	 * @param ProposalEntityType $entityType Entity type
	 *
	 * @return int Highest primary key touched in this batch.
	 */
	private function connectUnlinkedJudgments(
		IResultWrapper $unlinked,
		ProposalEntityType $entityType
	) {
		$tableHelper = new ProposalLinkTableHelper( $entityType );
		$indexStorage = JadeServices::getEntityIndexStorage();
		$lastId = 0;

		foreach ( $unlinked as $row ) {
			$title = Title::newFromRow( $row );
			$status = TitleHelper::parseTitleValue( $title->getTitleValue() );
			if ( !$status->isOK() ) {
				$this->error( "Failed to parse {$title}: {$status}\n" );
			} else {
				$judgmentTarget = $status->value;
				$judgmentPage = WikiPage::factory( $title );

				// Rebuild the index.
				if ( !$this->getOption( 'dry-run' ) ) {
					$indexStorage->insertIndex( $judgmentTarget, $judgmentPage );
				}

				// Summarize judgment.
				$judgmentContent = $judgmentPage->getContent();
				$status = ProposalSummarizer::getSummaryFromContent( $judgmentContent );
				if ( !$status->isOK() ) {
					$this->error( "Can't summarize content for {$title}: {$status}" );
				} else {
					$summaryValues = $status->value;
					if ( !$this->getOption( 'dry-run' ) ) {
						$indexStorage->updateSummary( $judgmentTarget, $summaryValues );
					}
				}
			}

			$lastId = $row->page_id;
		}
		MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->waitForReplication();

		$message = "Connected {$unlinked->numRows()} unlinked {$entityType} judgments.";
		if ( $this->getOption( 'dry-run' ) ) {
			$message .= ' (dry run)';
		}
		$this->output( "{$message}\n" );

		return $lastId;
	}

}

// @codeCoverageIgnoreStart
$maintClass = CleanJudgmentLinks::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
