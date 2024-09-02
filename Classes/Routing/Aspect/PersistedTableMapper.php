<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Routing\Aspect;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Routing\Aspect\SiteAccessorTrait;
use TYPO3\CMS\Core\Routing\Aspect\SiteLanguageAccessorTrait;
use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;
use TYPO3\CMS\Core\Site\SiteAwareInterface;
use TYPO3\CMS\Core\Site\SiteLanguageAwareInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Static Mapper with table as persistence layer
 *
 * routeEnhancers:
 *   ClubDirectoryPlugin:
 *     type: Extbase
 *     extension: Clubdirectory
 *     plugin: Clubdirectory
 *     routes:
 *       -
 *         routePath: '/show/{uid}/{title}'
 *         _controller: 'Club::show'
 *         _arguments:
 *           uid: club
 *           title: title
 *     requirements:
 *       title: '^[a-zA-Z0-9\-]+$'
 *     defaultController: 'Club::list'
 *     aspects:
 *       uid:
 *         type: PersistedTableMapper
 *       title:
 *         type: PersistedTableMapper
 */
class PersistedTableMapper implements StaticMappableAspectInterface, SiteLanguageAwareInterface, SiteAwareInterface
{
    use SiteAccessorTrait;

    use SiteLanguageAccessorTrait;

    protected array $settings;

    protected string $tableName = '';

    protected string $fieldName = '';

    public function __construct(array $settings)
    {
        $this->tableName = $settings['tableName'] ?? '';
        $this->fieldName = $settings['fieldName'] ?? '';

        $this->settings = $settings;
    }

    public function generate(string $value): ?string
    {
        // SlugHelper->sanitize will not replace / to -, so do it here
        $value = str_replace('/', '-', $value);

        $storedRoute = $this->getStoredRoute($value);
        if ($storedRoute === []) {
            $slugHelper = $this->getSlugHelper();
            $target = $slugHelper->sanitize($value);
            $this->setStoredRoute($value, $target);
        } else {
            $target = $storedRoute['target'];
        }

        return $target;
    }

    public function resolve(string $value): ?string
    {
        $storedRoute = $this->getStoredRoute('', $value);
        if ($storedRoute === []) {
            return null;
        }

        return $storedRoute['source'];
    }

    protected function getStoredRoute(string $source = '', string $target = ''): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_jwtools2_stored_routes');
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        $storedRoute = $queryBuilder
            ->select('*')
            ->from('tx_jwtools2_stored_routes')
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($this->getSiteLanguage()->getLanguageId(), Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'root_page',
                    $queryBuilder->createNamedParameter($this->getSite()->getRootPageId(), Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'tablename',
                    $queryBuilder->createNamedParameter($this->tableName, Connection::PARAM_STR),
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter($this->fieldName),
                ),
                $queryBuilder->expr()->eq(
                    $source ? 'source' : 'target',
                    $queryBuilder->createNamedParameter($source ?: $target),
                ),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        return $storedRoute ?: [];
    }

    protected function setStoredRoute(string $source = '', string $target = ''): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('tx_jwtools2_stored_routes');
        $connection->insert(
            'tx_jwtools2_stored_routes',
            [
                'crdate' => time(),
                'tstamp' => time(),
                'sys_language_uid' => $this->getSiteLanguage()->getLanguageId(),
                'root_page' => $this->getSite()->getRootPageId(),
                'tablename' => $this->tableName,
                'fieldname' => $this->fieldName,
                'source' => $source,
                'target' => $target,
            ],
            [
                Connection::PARAM_INT,
                Connection::PARAM_INT,
                Connection::PARAM_STR,
                Connection::PARAM_STR,
            ],
        );
    }

    protected function getSlugHelper(): SlugHelper
    {
        return GeneralUtility::makeInstance(
            SlugHelper::class,
            $this->tableName,
            $this->fieldName,
            [
                'fallbackCharacter' => '-',
                'prependSlash' => false,
            ],
        );
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
