<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\Jwtools2\Middleware\ElementBrowserSelectionGuardMiddleware;

return [
    'backend' => [
        'jweiland/jwtools2/element-browser-selection-guard' => [
            'target' => ElementBrowserSelectionGuardMiddleware::class,
            'after' => [
                'typo3/cms-backend/authentication',
            ],
        ],
    ],
];
