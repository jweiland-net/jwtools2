<?php
namespace JWeiland\Jwtools2\Domain\Repository;

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

use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use ApacheSolrForTypo3\Solr\Site;
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
     *
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
     *
     * @return Site
     */
    public function findByRootPage($rootPage)
    {
        /** @var SiteRepository $siteRepository */
        $siteRepository = GeneralUtility::makeInstance(SiteRepository::class);

        return $siteRepository->getSiteByRootPageId((int)$rootPage);
    }
}



