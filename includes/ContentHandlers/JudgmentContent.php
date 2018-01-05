<?php

namespace JADE\ContentHandlers;

use JsonContent;
use ParserOptions;
use ParserOutput;
use Title;

class JudgmentContent extends JsonContent {
	public function __construct( $text, $modelId = 'JadeJudgment' ) {
		parent::__construct( $text, $modelId );
	}

	/*
	protected function fillParserOutput(
		Title $title, $revId, ParserOptions $options, $generateHtml, ParserOutput &$output
	) {
		// e.g. $output->setText( $html );
	}*/
	//public function getTextForSearchIndex() {}
	//public function getWikitextForTransclusion() {}
	//public function getTextForSummary( $maxLength = 250 ) {}
	//public function getNativeData() {}
	//public function getSize() {}
	//public function copy() {}
	//public function isCountable( $hasLinks = null ) {}

	// TODO: run parent and custom validation
	# public function isValid
}
