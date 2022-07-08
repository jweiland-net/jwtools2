<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\XClasses;

use ApacheSolrForTypo3\Solr\System\Cache\TwoLevelCache;
use ApacheSolrForTypo3\Solr\System\Configuration\TypoScriptConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility class for tx_solr
 */
class Util extends \ApacheSolrForTypo3\Solr\Util
{
    /**
     * Loads the TypoScript configuration for a given page id and language.
     * Language usage may be disabled to get the default TypoScript
     * configuration.
     *
     * @param int $pageId Id of the (root) page to get the Solr configuration from.
     * @param string $path The TypoScript configuration path to retrieve.
     * @param bool $initializeTsfe Optionally initializes a full TSFE to get the configuration, defaults to FALSE
     * @param int $language System language uid, optional, defaults to 0
     * @param bool $useTwoLevelCache Flag to enable the two level cache for the typoscript configuration array
     * @return TypoScriptConfiguration The Solr configuration for the requested tree.
     */
    public static function getConfigurationFromPageId(
        $pageId,
        $path,
        $initializeTsfe = false,
        $language = 0,
        $useTwoLevelCache = true
    ): TypoScriptConfiguration {
        $pageId = self::getConfigurationPageIdToUse($pageId);

        static $configurationObjectCache = [];
        $cacheId = md5($pageId . '|' . $path . '|' . $language . '|' . ($initializeTsfe ? '1' : '0'));
        if (isset($configurationObjectCache[$cacheId])) {
            // SF: https://github.com/TYPO3-Solr/ext-solr/pull/2323/
            if ($initializeTsfe) {
                self::initializeTsfe($pageId, $language);
            }
            return $configurationObjectCache[$cacheId];
        }

        // If we're on UID 0, we cannot retrieve a configuration currently.
        // getRootline() below throws an exception (since #typo3-60 )
        // as UID 0 cannot have any parent rootline by design.
        if ($pageId == 0) {
            return $configurationObjectCache[$cacheId] = self::buildTypoScriptConfigurationFromArray([],
                $pageId,
                $language,
                $path);
        }

        if ($useTwoLevelCache) {
            /** @var $cache TwoLevelCache */
            $cache = GeneralUtility::makeInstance(
                TwoLevelCache::class,
                /** @scrutinizer ignore-type */
                'tx_solr_configuration'
            );
            $configurationArray = $cache->get($cacheId);
        }

        if (!empty($configurationArray)) {
            // we have a cache hit and can return it.
            return $configurationObjectCache[$cacheId] = self::buildTypoScriptConfigurationFromArray(
                $configurationArray,
                $pageId,
                $language,
                $path
            );
        }

        // we have nothing in the cache. We need to build the configurationToUse
        $configurationArray = self::buildConfigurationArray($pageId, $path, $initializeTsfe, $language);

        if ($useTwoLevelCache && isset($cache)) {
            $cache->set($cacheId, $configurationArray);
        }

        return $configurationObjectCache[$cacheId] = self::buildTypoScriptConfigurationFromArray(
            $configurationArray,
            $pageId,
            $language,
            $path
        );
    }
}
