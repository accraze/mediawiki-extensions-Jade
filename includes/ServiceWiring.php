<?php

namespace JADE;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use RequestContext;

return [

	'JADEJudgmentValidator' => function ( MediaWikiServices $services ) {
		return new JudgmentValidator(
			RequestContext::getMain()->getConfig(),
			LoggerFactory::getInstance( 'JADE' ),
			$services->getRevisionStore()
		);
	},

];
