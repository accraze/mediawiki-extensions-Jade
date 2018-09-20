<?php

namespace JADE;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use RequestContext;

return [

	'JADEJudgmentFormatter' => function ( MediaWikiServices $services ) {
		return new PageFormatter();
	},

	'JADEEntityJudgmentSetStorage' => function ( MediaWikiServices $services ) {
		return new PageEntityJudgmentSetStorage();
	},

	'JADEJudgmentValidator' => function ( MediaWikiServices $services ) {
		return new JudgmentValidator(
			RequestContext::getMain()->getConfig(),
			LoggerFactory::getInstance( 'JADE' ),
			$services->getRevisionStore()
		);
	},

];
