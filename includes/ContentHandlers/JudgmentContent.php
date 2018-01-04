<?php

namespace JADE\ContentHandlers;

use AbstractContent;

class JudgmentContent extends \AbstractContent {
	public function __construct( $modelId = 'jade_judgment' ) {
		parent::__construct( $modelId );
	}

	protected function fillParserOutput(
		Title $title, $revId, ParserOptions $options, $generateHtml, ParserOutput &$output
	) {
		// e.g. $output->setText( $html );
	}
	public function getTextForSearchIndex() {}
	public function getWikitextForTransclusion() {}
	public function getTextForSummary( $maxLength = 250 ) {}
	public function getNativeData() {}
	public function getSize() {}
	public function copy() {}
	public function isCountable( $hasLinks = null ) {}
}
