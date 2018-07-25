<?php

namespace JADE;

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use RequestContext;

return [

	'JADEAppendCreator' => function ( MediaWikiServices $services ) {
		return new JudgmentAppendCreator(
			$services->getService( 'JADEJudgmentFormatter' ),
			$services->getService( 'JADEEntityJudgmentSetStorage' )
		);
	},

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
