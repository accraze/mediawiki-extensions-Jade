<?php

namespace JADE;

use MediaWiki\MediaWikiServices;

class JADEServices {

	/** @return PageFormatter */
	public static function getJudgmentFormatter() {
		return MediaWikiServices::getInstance()->getService( 'JADEJudgmentFormatter' );
	}

	/** @return JudgmentStorage */
	public static function getEntityJudgmentSetStorage() {
		return MediaWikiServices::getInstance()->getService( 'JADEEntityJudgmentSetStorage' );
	}

	/** @return JudgmentValidator */
	public static function getJudgmentValidator() {
		return MediaWikiServices::getInstance()->getService( 'JADEJudgmentValidator' );
	}

}
