<?php

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\Jwtools2\Controller;

/**
 * Definitions for routes provided by EXT:backend
 * Contains all AJAX-based routes for entry points
 * Currently the "access" property is only used so no token creation + validation is made
 * but will be extended further.
 */
return [
    'jwtools2_updateFileMetadata' => [
        'path' => '/jwtools2/updateFileMetadata',
        'target' => Controller\Ajax\SysFileController::class . '::updateFileMetadataAction',
    ],
    'jwtools2_clearIndex' => [
        'path' => '/jwtools2/clearIndex',
        'target' => Controller\Ajax\AjaxSolrController::class . '::clearIndexAction',
    ],
    'jwtools2_createSolrIndexQueue' => [
        'path' => '/jwtools2/createSolrIndexQueue',
        'target' => Controller\Ajax\AjaxSolrController::class . '::createIndexQueueAction',
    ],
    'jwtools2_getSolrProgress' => [
        'path' => '/jwtools2/getSolrProgress',
        'target' => Controller\Ajax\AjaxSolrController::class . '::getProgressAction',
    ],
];
