<?php
use JWeiland\Jwtools2\Controller;

/**
 * Definitions for routes provided by EXT:backend
 * Contains all AJAX-based routes for entry points
 *
 * Currently the "access" property is only used so no token creation + validation is made
 * but will be extended further.
 */
return [
    'jwtools2_clearIndex' => [
        'path' => '/jwtools2/clearIndex',
        'target' => Controller\Ajax\AjaxSolrController::class . '::clearIndexAction'
    ],
];
