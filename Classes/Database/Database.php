<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Database;

use JWeiland\Jwtools2\Database\Query\Restriction\BackendRestrictionContainer;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class is a little helper to build your own QueryBuilder incl. correct Restrictions
 */
class Database
{
    /**
     * Get QueryBuilder for table.
     * If only table is given it calls default getQueryBuilder from ConnectionPool.
     * If more arguments are given we build our own special QueryBuilder
     *
     * @param string $tableName
     * @param bool $useRestrictionsForCurrentTypo3Mode If true it will automatically implement a RestrictionContainer fitting to TYPO3_MODE
     * @param QueryRestrictionContainerInterface $restrictionContainer Implement your own RestrictionContainer. Only available if $useRestrictionsForCurrentTypo3Mode is set to false
     * @return QueryBuilder
     */
    public static function getQueryBuilderForTable(
        $tableName,
        $useRestrictionsForCurrentTypo3Mode = true,
        QueryRestrictionContainerInterface $restrictionContainer = null
    ) {
        if ($useRestrictionsForCurrentTypo3Mode) {
            if (TYPO3_MODE === 'FE') {
                $restrictionContainer = GeneralUtility::makeInstance(FrontendRestrictionContainer::class);
            } else {
                $restrictionContainer = GeneralUtility::makeInstance(BackendRestrictionContainer::class);
            }
        }

        if ($restrictionContainer !== null) {
            $connection = self::getConnectionForTable($tableName);
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(
                QueryBuilder::class,
                $connection,
                $restrictionContainer
            );
            return $queryBuilder;
        }
        return self::getConnectionPool()->getQueryBuilderForTable($tableName);
    }

    /**
     * Creates a connection object based on the specified table name.
     *
     * @param string $tableName
     * @return Connection
     */
    public static function getConnectionForTable($tableName)
    {
        return self::getConnectionPool()->getConnectionForTable($tableName);
    }

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    public static function getConnectionPool()
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        return $connectionPool;
    }
}
