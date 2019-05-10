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

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Additional field provider for the index queue worker task
 */
class IndexQueueWorkerTaskAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * Used to define fields to provide the TYPO3 site to index and number of
     * items to index per run when adding or editing a task.
     *
     * @param array $taskInfo reference to the array containing the info used in the add/edit form
     * @param AbstractTask $task when editing, reference to the current task object. Null when adding.
     * @param SchedulerModuleController $schedulerModule : reference to the calling object (Scheduler's BE module)
     *
     * @return array Array containing all the information pertaining to the additional fields
     *                    The array is multidimensional, keyed to the task class name and each field's id
     *                    For each field it provides an associative sub-array with the following:
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        /** @var \JWeiland\Jwtools2\Task\IndexQueueWorkerTask $task */
        $additionalFields = [];

        // Documents to index
        if ($schedulerModule->CMD == 'add') {
            $taskInfo['documentsToIndexLimit'] = 50;
        }

        if ($schedulerModule->CMD == 'edit') {
            $taskInfo['documentsToIndexLimit'] = $task->getDocumentsToIndexLimit();
        }

        $additionalFields['documentsToIndexLimit'] = [
            'code' => '<input type="text" name="tx_scheduler[documentsToIndexLimit]" value="' . htmlspecialchars($taskInfo['documentsToIndexLimit']) . '" />',
            'label' => LocalizationUtility::translate('indexqueueworker_field_documentsToIndexLimit', 'Jwtools2'),
            'cshKey' => '',
            'cshLabel' => ''
        ];

        // Max sites per run
        if ($schedulerModule->CMD == 'add') {
            $taskInfo['maxSitesPerRun'] = 10;
        }

        if ($schedulerModule->CMD == 'edit') {
            $taskInfo['maxSitesPerRun'] = $task->getMaxSitesPerRun();
        }

        $additionalFields['maxSitesPerRun'] = [
            'code' => '<input type="text" name="tx_scheduler[maxSitesPerRun]" value="' . htmlspecialchars($taskInfo['maxSitesPerRun']) . '" />',
            'label' => LocalizationUtility::translate('indexqueueworker_field_maxSitesPerRun', 'Jwtools2'),
            'cshKey' => '',
            'cshLabel' => ''
        ];

        return $additionalFields;
    }

    /**
     * Checks any additional data that is relevant to this task. If the task
     * class is not relevant, the method is expected to return TRUE
     *
     * @param array $submittedData reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule reference to the calling object (Scheduler's BE module)
     *
     * @return bool True if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule)
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
     *
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        /** @var \JWeiland\Jwtools2\Task\IndexQueueWorkerTask $task */
        $task->setDocumentsToIndexLimit($submittedData['documentsToIndexLimit']);
        $task->setMaxSitesPerRun($submittedData['maxSitesPerRun']);
    }
}
