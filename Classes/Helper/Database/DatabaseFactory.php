<?php

class Tx_T3monitor_Helper_DatabaseFactory
{

    /**
     * @var Tx_T3monitor_Helper_DatabaseInterface
     */
    protected static $instance;

    /**
     * @return Tx_T3monitor_Helper_DatabaseInterface
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(\TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version()) < 9000000) {
                self::$instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_T3monitor_Helper_Compatibility8Database');
            } else {
                self::$instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Tx_T3monitor_Helper_Database');
            }
        }

        return self::$instance;
    }
}