<?php
/**
 * Translations of the namespaces introduced by JADE.
 *
 * @file
 */

require_once __DIR__ . '/defines.php';

$namespaceNames = [];
$namespaceAliases = [];

/** English */
$namespaceNames['en'] = [
	NS_JUDGMENT => 'Judgment',
	NS_JUDGMENT_TALK => 'Judgment_talk',
];

// "judgment" and "judgement" are alternative spellings, so allow either.
$namespaceAliases['en'] = [
	'Judgement' => NS_JUDGMENT,
];

/** Spanish */
$namespaceNames['es'] = [
	NS_JUDGMENT => 'Jade',
	NS_JUDGMENT_TALK => 'Jade_discusión',
];
