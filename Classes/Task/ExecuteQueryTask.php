<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Task;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Task to execute recurring SQL-Statements
 */
class ExecuteQueryTask extends AbstractTask
{
    /**
     * @var string
     */
    protected $sqlQuery = '';

    /**
     * Get and execute SQL-Query with Doctrine
     *
     * @return bool Returns TRUE on success, FALSE if SQL-Query fails
     */
    public function execute(): bool
    {
        try {
            $connection = $this->getConnectionPool()->getConnectionByName('Default');
            return $connection->query($this->sqlQuery)->execute();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getSqlQuery(): string
    {
        return $this->sqlQuery;
    }

    /**
     * @param string $sqlQuery
     */
    public function setSqlQuery(string $sqlQuery): void
    {
        $this->sqlQuery = $sqlQuery;
    }

    /**
     * Get TYPO3s Connection Pool
     *
     * @return ConnectionPool
     */
    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
