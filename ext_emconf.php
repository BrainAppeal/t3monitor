<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "brainmonitor".
 *
 * Auto generated 19-11-2014 14:28
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TYPO3 Monitor',
	'description' => 'Monitors typo3 installation for updates and security problems (Requires an account on http://www.typo3-monitor.de/)',
	'category' => 'misc',
	'author' => 'Gert Hammes',
	'author_email' => 'info@brain-appeal.com',
	'author_company' => 'Brain Appeal GmbH',
	'shy' => '',
	'version' => '2.0.10',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.5.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:25:{s:21:"ext_conf_template.txt";s:4:"ec02";s:12:"ext_icon.gif";s:4:"a2ef";s:17:"ext_localconf.php";s:4:"d667";s:25:"Classes/Helper/Config.php";s:4:"122e";s:27:"Classes/Helper/Database.php";s:4:"f239";s:29:"Classes/Helper/Encryption.php";s:4:"88b5";s:25:"Classes/Helper/Logger.php";s:4:"4aa3";s:24:"Classes/Helper/Timer.php";s:4:"6056";s:28:"Classes/Reports/Abstract.php";s:4:"03e5";s:32:"Classes/Reports/Applications.php";s:4:"3fac";s:28:"Classes/Reports/Database.php";s:4:"59ba";s:24:"Classes/Reports/Disc.php";s:4:"973d";s:29:"Classes/Reports/Extension.php";s:4:"170f";s:25:"Classes/Reports/Links.php";s:4:"6fb0";s:27:"Classes/Reports/Reports.php";s:4:"7300";s:28:"Classes/Reports/Security.php";s:4:"060c";s:34:"Classes/Reports/SecurityCompat.php";s:4:"b538";s:26:"Classes/Reports/Server.php";s:4:"6d33";s:26:"Classes/Reports/SysLog.php";s:4:"bdca";s:33:"Classes/Service/Compatibility.php";s:4:"dbce";s:30:"Classes/Service/Dispatcher.php";s:4:"8ce5";s:40:"Resources/Private/Language/locallang.xml";s:4:"c434";s:43:"Resources/Private/Language/locallang_be.xml";s:4:"733e";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"125f";s:35:"Resources/Public/Icons/relation.gif";s:4:"e615";}',
);

?>