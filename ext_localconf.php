<?php

use BrainAppeal\T3monitor\Service\Dispatcher;

if (!defined('TYPO3') && !defined('TYPO3_MODE')) {
    die();
}
// Middleware \BrainAppeal\T3monitor\Middleware\FetchMonitorData is used for TYPO3 >= 12; this is used for previous releases
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['t3monitor'] = Dispatcher::class . '::processRequest';

$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^t3monitor';
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^secret';
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^reports';
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^last_check';
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^extended';
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = '^only_check';
