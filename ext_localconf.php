<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (class_exists('\TYPO3\CMS\Core\Utility\ExtensionManagementUtility')) {
    $eidInclude = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('t3monitor') . 'Classes/Service/EidRunner.php';
} elseif (class_exists('t3lib_extMgm')) {
    /** @noinspection PhpUndefinedClassInspection */
    $eidInclude = t3lib_extMgm::extPath('t3monitor').'Classes/Service/EidRunner.php';
}
if (class_exists('\TYPO3\CMS\Core\Information\Typo3Version')) {
    $eidInclude = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('t3monitor') . 'Classes/Service/Dispatcher.php';
    $cmsVersion = (string) \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
    if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger($cmsVersion) > 10000000) {
        require_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('t3monitor') . 'Classes/Service/Dispatcher.php';
        $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['t3monitor'] = 'Tx_T3monitor_Service_Dispatcher::processRequest';
        $eidInclude = '';
    }
}
if (!empty($eidInclude)) {
    $TYPO3_CONF_VARS['FE']['eID_include']['brainmonitor'] = $eidInclude;
    $TYPO3_CONF_VARS['FE']['eID_include']['t3monitor'] = $eidInclude;
}