<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\EventListener;

use ApacheSolrForTypo3\Solr\Event\Indexing\BeforeItemIsIndexedEvent;
use TYPO3\CMS\Core\Registry;

readonly class IndexServiceEventListener
{
    public function __construct(private Registry $registry) {}

    public function __invoke(BeforeItemIsIndexedEvent $event): void
    {
        $item = $event->getItem();

        $this->registry->set('jwtools2-solr', 'indexQueueUid', $item->getIndexQueueUid());
        $this->registry->set('jwtools2-solr', 'memoryPeakUsage', memory_get_peak_usage(true));
    }
}
