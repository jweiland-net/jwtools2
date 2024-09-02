<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Domain\Repository;

use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\Domain\Site\SiteRepository;
use Doctrine\DBAL\Driver\Exception as DBALDriverException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SolrRepository
 */
class SolrRepository
{
    /**
     * Gets all available TYPO3 sites with Solr configured.
     *
     * @return Site[] An array of available sites
     */
    public function findAllAvailableSites(bool $stopOnInvalidSite = false): array
    {
        try {
            return GeneralUtility::makeInstance(SiteRepository::class)->getAvailableSites($stopOnInvalidSite);
        } catch (DBALDriverException | \Throwable $exception) {
            return [];
        }
    }

    /**
     * Get site by root page
     */
    public function findByRootPage(int $rootPage): ?Site
    {
        try {
            return GeneralUtility::makeInstance(SiteRepository::class)->getSiteByRootPageId($rootPage);
        } catch (DBALDriverException $exception) {
            return null;
        }
    }
}
