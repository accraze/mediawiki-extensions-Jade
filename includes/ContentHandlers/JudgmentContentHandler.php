<?php

namespace JADE\ContentHandlers;

use Content;
use ContentHandler;

class JudgmentContentHandler extends ContentHandler {
	public function __construct( $modelId = 'jade_judgment' ) {
		parent::__construct( $modelId, [ CONTENT_FORMAT_JSON ] );
	}

	protected function getContentClass() {
		return JudgmentContent::class;
	}

	public function getActionOverrides() {
		return [
			'edit' => JudgmentEditAction::class,
		];
	}

	public function supportsDirectEditing() {}
	public function serializeContent( Content $content, $format = null ) {}
	public function unserializeContent( $blob, $format = null ) {}
	public function makeEmptyContent() {}
}
