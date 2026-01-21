<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\Jwtools2\Controller\SolrController;
use JWeiland\Jwtools2\Controller\ToolsController;

/**
 * Definitions for modules provided by EXT:jwtools2
 */
return [
    'tools_ts_jwtools2' => [
        'parent' => 'tools',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
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
