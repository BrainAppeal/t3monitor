<?php

declare(strict_types=1);

use BrainAppeal\T3monitor\Middleware\FetchMonitorData;
use TYPO3\CMS\Core\Information\Typo3Version;

if ((new Typo3Version())->getMajorVersion() < 14) {
    return [
        'frontend' => [
            't3monitor-handler' => [
                'target' => FetchMonitorData::class,
                'before' => [
                    'typo3/cms-frontend/output-compression',
                ],
                'after' => [
                    'typo3/cms-core/normalized-params-attribute',
                    'typo3/cms-frontend/site',
                    'typo3/cms-frontend/tsfe',
                ],
            ]
        ]
    ];
}
return [
    'frontend' => [
        't3monitor-handler' => [
            'target' => FetchMonitorData::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
            'after' => [
                'typo3/cms-core/normalized-params-attribute',
                'typo3/cms-frontend/site',
            ],
        ]
    ]
];
