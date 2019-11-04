<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['file_list'][] = 'defines.php';
$cfg['exclude_file_list'][] = 'maintenance/CleanJudgmentLinks.php';
$cfg['exclude_file_list'][] = 'includes/Hooks/LinkTableHooks.php';
$cfg['exclude_file_list'][] = 'includes/EntityBuilder.php';
$cfg['exclude_analysis_directory_list'][] = 'includes/Api/';

return $cfg;
