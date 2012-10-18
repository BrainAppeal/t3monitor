<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$eidInclude = t3lib_extMgm::extPath($_EXTKEY).'Classes/Service/Dispatcher.php';
$TYPO3_CONF_VARS['FE']['eID_include']['met_t3monitor'] = $eidInclude;
?>