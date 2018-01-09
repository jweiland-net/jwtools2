<?php

namespace JWeiland\Jwtools2\Hooks;

/*
 * This file is part of the jwtools2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use ApacheSolrForTypo3\Solr\IndexQueue\Item;
use ApacheSolrForTypo3\Solr\Task\IndexQueueWorkerTask;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class IndexService
 *
 * @package JWeiland\Jwtools2\Hooks
 */
class IndexService
{
    /**
     * Save current Item ID in sys_registry for debugging
     *
     * @param Item $item
     * @param IndexQueueWorkerTask|null $task
     * @param string $uniqueId
     *
     * @return void
     */
    public function beforeIndexItem(Item $item, $task, $uniqueId = '')
    {
        /** @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->set('jwtools2-solr', 'indexQueueUid', $item->getIndexQueueUid());
        $registry->set('jwtools2-solr', 'memoryPeakUsage', memory_get_peak_usage(true));
    }
}
