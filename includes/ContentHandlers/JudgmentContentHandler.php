<?php

namespace JADE\ContentHandlers;

use JsonContentHandler;
use Title;

class JudgmentContentHandler extends JsonContentHandler {

	public function __construct( $modelId = 'JadeJudgment' ) {
		parent::__construct( $modelId );
	}

	protected function getContentClass() {
		return JudgmentContent::class;
	}

	public function canBeUsedOn( Title $title ) {
		return $title->inNamespace( NS_JADE );
	}

}
