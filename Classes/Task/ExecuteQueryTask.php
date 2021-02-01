<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Task;

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

    public function execute(): bool
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

    public function setSqlQuery(string $sqlQuery): void
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
    public function addMessage(string $message, int $severity = FlashMessage::OK): void
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
