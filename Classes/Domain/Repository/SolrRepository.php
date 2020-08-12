<?php

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Domain\Repository;

use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SolrRepository
 */
class SolrRepository
{
    /**
     * Gets all available TYPO3 sites with Solr configured.
     *
     * @param bool $stopOnInvalidSite
     * @return Site[] An array of available sites
     */
    public function findAllAvailableSites($stopOnInvalidSite = false)
    {
        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);

        return $siteRepository->getAvailableSites($stopOnInvalidSite);
    }

    /**
     * Get site by root page
     *
     * @param int $rootPage
     * @return Site
     */
    public function findByRootPage($rootPage)
    {
        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);

        return $siteRepository->getSiteByRootPageId((int)$rootPage);
    }
}
