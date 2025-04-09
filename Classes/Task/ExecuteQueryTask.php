<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Task;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Task to execute recurring SQL-Statements
 */
class ExecuteQueryTask extends AbstractTask
{
    protected string $sqlQuery = '';

    public function execute(): bool
    {
        try {
            $connection = $this->getConnectionPool()->getConnectionByName('Default');
            $sqlQueries = preg_split('/;\v+/', $this->sqlQuery);
            if ($sqlQueries === false) {
                $this->addMessage('PCRE error occurred while parsing the query string');
            }

            $sqlQueries = array_filter($sqlQueries);
            if ($sqlQueries === []) {
                $this->addMessage('No queries for execution found');
                return false;
            }

            foreach ($sqlQueries as $sqlQuery) {
                // check $sqlQuery ends with a semi-colon otherwise add it
                if (!str_ends_with($sqlQuery, ';')) {
                    $sqlQuery .= ';';
                }

                $connection->executeStatement($sqlQuery);
            }

            $this->addMessage(
                'SQL Queries executed successfully',
            );

            return true;
        } catch (\Exception $exception) {
            $this->addMessage(
                'Error occurred: ' . $exception->getMessage(),
                ContextualFeedbackSeverity::ERROR,
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
     */
    public function addMessage(string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK): void
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
