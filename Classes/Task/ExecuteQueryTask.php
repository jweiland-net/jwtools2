<?php
namespace JWeiland\Jwtools2\Task;

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
    public function execute()
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
    public function setSqlQuery(string $sqlQuery)
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
