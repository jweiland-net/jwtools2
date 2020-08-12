<?php

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Hooks;

use ApacheSolrForTypo3\Solr\IndexQueue\Item;
use ApacheSolrForTypo3\Solr\Task\IndexQueueWorkerTask;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class IndexService
 */
class IndexService
{
    /**
     * Save current Item ID in sys_registry for debugging
     *
     * @param Item $item
     * @param IndexQueueWorkerTask|null $task
     * @param string $uniqueId
     */
    public function beforeIndexItem(Item $item, $task, $uniqueId = '')
    {
        /** @var Registry $registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->set('jwtools2-solr', 'indexQueueUid', $item->getIndexQueueUid());
        $registry->set('jwtools2-solr', 'memoryPeakUsage', memory_get_peak_usage(true));
    }
}
