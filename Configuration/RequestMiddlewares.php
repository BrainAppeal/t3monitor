<?php
return [
    'frontend' => [
        't3monitor-handler' => [
            'target' => \BrainAppeal\T3monitor\Middleware\FetchMonitorData::class,
            'before' => [
                'typo3/cms-frontend/output-compression',
            ],
            'after' => [
                'typo3/cms-frontend/site',
                'typo3/cms-frontend/tsfe',
            ],
        ]
    ]
];