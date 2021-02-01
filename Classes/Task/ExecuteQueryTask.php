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
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
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
            $sqlQueries = preg_split('/;\v+/', $this->sqlQuery);
            foreach ($sqlQueries as $sqlQuery) {
                $sqlQuery = trim($sqlQuery);
                if ($sqlQuery) {
                    $connection->query($sqlQuery)->execute();
                }
            }

            $this->addMessage(
                'SQL Queries executed successfully'
            );

            return true;
        } catch (\Exception $e) {
            $this->addMessage(
                'Error occurred: ' . $e->getMessage(),
                FlashMessage::ERROR
            );

            return false;
        }
    }

    public function getSqlQuery(): string
    {
        return $this->sqlQuery;
    }

    public function setSqlQuery(string $sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
    }

    /**
     * This method is used to add a message to the internal queue
     *
     * @param string $message The message itself
     * @param int $severity Message level (according to \TYPO3\CMS\Core\Messaging\FlashMessage class constants)
     * @throws \Exception
     */
    public function addMessage(string $message, int $severity = FlashMessage::OK)
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
