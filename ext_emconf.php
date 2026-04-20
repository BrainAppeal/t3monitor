<?php

declare(strict_types=1);

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
	'version' => '2.2.0',
	'state' => 'stable',
	'constraints' => [
		'depends' => [
			'typo3' => '10.4.30-14.3.99',
        ],
		'conflicts' => [
        ],
		'suggests' => [
        ],
    ],
];
