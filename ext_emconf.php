<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "t3monitor".
 *
 * Auto generated 19-11-2014 14:28
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'T3Monitor',
	'description' => 'Monitors TYPO3 installation for updates and security issues (Requires an account on http://www.t3monitor.de)',
	'category' => 'misc',
	'author' => 'T3Monitor Team',
	'author_email' => 'feedback@t3monitor.de',
	'author_company' => 'Brain Appeal GmbH',
	'shy' => '',
	'version' => '1.2.2',
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
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.5.0-9.5.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

?>