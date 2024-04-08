<?php

declare(strict_types=1);

use JWeiland\Jwtools2\Controller\SolrController;
use JWeiland\Jwtools2\Controller\ToolsController;

/**
 * Definitions for modules provided by EXT:jwtools2
 */
return [
    'tools_ts_jwtools2' => [
        'parent' => 'tools',
        'position' => ['after' => 'web_info'],
        'access' => 'user,group',
        'workspaces' => 'live',
        'path' => '/module/tools/jwtools2',
        'labels' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang_module_tools.xlf',
        'iconIdentifier' => 'ext-jwtools2-be-module-icon',
        'extensionName' => 'jwtools2',
        'controllerActions' => [
            ToolsController::class => [
                'overview',
            ],
            SolrController::class => [
                'list', 'show', 'showIndexQueue', 'indexOneRecord', 'showClearIndexForm', 'clearIndex', 'showClearFullIndexForm',
            ],
        ],
    ],
];
