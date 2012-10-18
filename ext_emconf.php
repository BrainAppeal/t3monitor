<?php

########################################################################
# Extension Manager/Repository config file for ext "met_t3monitor".
#
# Auto generated 20-03-2012 15:41
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TYPO3 Monitor',
	'description' => 'Monitors typo3 installation for updates and security problems',
	'category' => 'misc',
	'author' => 'Gert Hammes',
	'author_email' => 'info@meteos.de',
	'author_company' => 'METEOS Deutschland',
	'shy' => '',
	'version' => '2.0.4',
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
			'php' => '5.0.0-0.0.0',
			'typo3' => '4.2.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:22:{s:21:"ext_conf_template.txt";s:4:"5ec3";s:12:"ext_icon.gif";s:4:"a2ef";s:17:"ext_localconf.php";s:4:"f82f";s:25:"Classes/Helper/Config.php";s:4:"ad04";s:27:"Classes/Helper/Database.php";s:4:"643e";s:29:"Classes/Helper/Encryption.php";s:4:"3575";s:25:"Classes/Helper/Logger.php";s:4:"f93a";s:24:"Classes/Helper/Timer.php";s:4:"9391";s:28:"Classes/Reports/Abstract.php";s:4:"3ac0";s:28:"Classes/Reports/Database.php";s:4:"5017";s:24:"Classes/Reports/Disc.php";s:4:"f955";s:29:"Classes/Reports/Extension.php";s:4:"078a";s:27:"Classes/Reports/Reports.php";s:4:"7e76";s:28:"Classes/Reports/Security.php";s:4:"7801";s:34:"Classes/Reports/SecurityCompat.php";s:4:"2625";s:26:"Classes/Reports/Server.php";s:4:"4dbb";s:26:"Classes/Reports/SysLog.php";s:4:"1127";s:30:"Classes/Service/Dispatcher.php";s:4:"058d";s:40:"Resources/Private/Language/locallang.xml";s:4:"c434";s:43:"Resources/Private/Language/locallang_be.xml";s:4:"0140";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"9130";s:35:"Resources/Public/Icons/relation.gif";s:4:"e615";}',
);

?>