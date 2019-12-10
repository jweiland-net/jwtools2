<?php
declare(strict_types = 1);
namespace JWeiland\Jwtools2\XClasses;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/**
 * A general purpose indexer to be used for indexing of any kind of regular
 * records like tt_news, tt_address, and so on.
 * Specialized indexers can extend this class to handle advanced stuff like
 * category resolution in tt_news or file indexing.
 */
class Indexer extends \ApacheSolrForTypo3\Solr\IndexQueue\Indexer
{
    /**
     * Gets the configuration how to process an item's fields for indexing.
     *
     * @param Item $item An index queue item
     * @param int $language Language ID
     * @throws \RuntimeException
     * @return array Configuration array from TypoScript
     */
    protected function getItemTypeConfiguration(Item $item, $language = 0)
    {
        $indexConfigurationName = $item->getIndexingConfigurationName();
        $fields = $this->getFieldConfigurationFromItemRecordPage($item, $language, $indexConfigurationName);
        if (!$this->isRootPageIdPartOfRootLine($item) || count($fields) === 0) {
            $fields = $this->getFieldConfigurationFromItemRootPage($item, $language, $indexConfigurationName);
            if (count($fields) === 0) {
                throw new \RuntimeException('The item indexing configuration "' . $item->getIndexingConfigurationName() .
                    '" on root page uid ' . $item->getRootPageUid() . ' could not be found!', 1455530112);
            }
        }

        return $fields;
    }

    /**
     * SF: https://github.com/TYPO3-Solr/ext-solr/pull/2324/
     *
     * In case of additionalStoragePid config recordPageId can be outsite of siteroot.
     * In that case we should not read TS config of foreign siteroot.
     *
     * @param Item $item
     * @return bool
     */
    protected function isRootPageIdPartOfRootLine(Item $item)
    {
        // Solr is not casting the property root to int in their constructor this is why we cast it here
        $rootPageId = (int)$item->getRootPageUid();
        $buildRootlineWithPid = $item->getRecordPageId();
        if ($item->getType() === 'pages') {
            $buildRootlineWithPid = $item->getRecordUid();
        }
        $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $buildRootlineWithPid);
        $rootline = $rootlineUtility->get();

        $pageInRootline = array_filter($rootline, function ($page) use ($rootPageId) {
            return (int)$page['uid'] === $rootPageId;
        });
        return !empty($pageInRootline);
    }
}
