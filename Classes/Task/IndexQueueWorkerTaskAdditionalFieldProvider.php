<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Task;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Additional field provider for the index queue worker task
 */
class IndexQueueWorkerTaskAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * Used to define fields to provide the TYPO3 site to index and number of
     * items to index per run when adding or editing a task.
     *
     * @param array $taskInfo reference to the array containing the info used in the add/edit form
     * @param AbstractTask $task when editing, reference to the current task object. Null when adding.
     * @param SchedulerModuleController $schedulerModule : reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     *                    The array is multidimensional, keyed to the task class name and each field's id
     *                    For each field it provides an associative sub-array with the following:
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        /** @var IndexQueueWorkerTask $task */
        $additionalFields = [];

        $currentAction = $schedulerModule->getCurrentAction();

        // Documents to index
        if ($currentAction->equals(Action::ADD)) {
            $taskInfo['documentsToIndexLimit'] = 50;
            $taskInfo['maxSitesPerRun'] = 10;
        }

        if ($currentAction->equals(Action::EDIT)) {
            $taskInfo['documentsToIndexLimit'] = $task->getDocumentsToIndexLimit();
            $taskInfo['maxSitesPerRun'] = $task->getMaxSitesPerRun();
        }

        $additionalFields['documentsToIndexLimit'] = [
            'code' => '<input type="text" name="tx_scheduler[documentsToIndexLimit]" value="' . $taskInfo['documentsToIndexLimit'] . '" />',
            'label' => LocalizationUtility::translate('indexqueueworker_field_documentsToIndexLimit', 'Jwtools2'),
            'cshKey' => '',
            'cshLabel' => '',
        ];

        $additionalFields['maxSitesPerRun'] = [
            'code' => '<input type="text" name="tx_scheduler[maxSitesPerRun]" value="' . $taskInfo['maxSitesPerRun'] . '" />',
            'label' => LocalizationUtility::translate('indexqueueworker_field_maxSitesPerRun', 'Jwtools2'),
            'cshKey' => '',
            'cshLabel' => '',
        ];

        return $additionalFields;
    }

    /**
     * Checks any additional data that is relevant to this task. If the task
     * class is not relevant, the method is expected to return TRUE
     *
     * @param array $submittedData reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        // escape limit
        $submittedData['documentsToIndexLimit'] = (int)$submittedData['documentsToIndexLimit'];
        $submittedData['maxSitesPerRun'] = (int)$submittedData['maxSitesPerRun'];

        return true;
    }

    /**
     * Saves any additional input into the current task object if the task
     * class matches.
     *
     * @param array $submittedData array containing the data submitted by the user
     * @param AbstractTask $task reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        /** @var IndexQueueWorkerTask $task */
        $task->setDocumentsToIndexLimit((int)$submittedData['documentsToIndexLimit']);
        $task->setMaxSitesPerRun((int)$submittedData['maxSitesPerRun']);
    }
}
