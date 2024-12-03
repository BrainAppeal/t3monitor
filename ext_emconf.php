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

$EM_CONF['t3monitor'] = [
	'title' => 'T3Monitor',
	'description' => 'Monitors TYPO3 installation for updates and security issues (Requires an account on www.t3monitor.de)',
	'category' => 'misc',
	'author' => 'T3Monitor Team',
	'author_email' => 'feedback@t3monitor.de',
	'author_company' => 'Brain Appeal GmbH',
	'shy' => '',
	'version' => '2.1.0',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'constraints' => [
		'depends' => [
			'typo3' => '10.4.30-13.4.99',
        ],
		'conflicts' => [
        ],
		'suggests' => [
        ],
    ],
];
