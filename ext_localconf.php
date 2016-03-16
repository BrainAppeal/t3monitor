<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (class_exists('\TYPO3\CMS\Core\Utility\ExtensionManagementUtility')) {
    $eidInclude = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Service/Dispatcher.php';
} elseif (class_exists('t3lib_extMgm')) {
    $eidInclude = t3lib_extMgm::extPath($_EXTKEY).'Classes/Service/Dispatcher.php';
}
if (!empty($eidInclude)) {
    $TYPO3_CONF_VARS['FE']['eID_include']['brainmonitor'] = $eidInclude;
    $TYPO3_CONF_VARS['FE']['eID_include']['t3monitor'] = $eidInclude;
}