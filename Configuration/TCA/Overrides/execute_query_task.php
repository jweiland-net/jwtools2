<?php

declare(strict_types=1);
defined('TYPO3') || die();

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\Jwtools2\Task\ExecuteQueryTask;
use JWeiland\Jwtools2\Task\IndexQueueWorkerTask;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:executeQueryTask.title',
        'description' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:executeQueryTask.description',
        'value' => ExecuteQueryTask::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'iconOverlay' => 'content-clock',
        'group' => 'scheduler',
    ],
    '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
        --div--;core.form.tabs:timing,
            --palette--;;execution,
        --div--;core.form.tabs:access,
            disable,
        --div--;core.form.tabs:extended,',
    [],
    '',
    'tx_scheduler_task',
);

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:indexqueueworker_title',
        'description' => 'LLL:EXT:jwtools2/Resources/Private/Language/locallang.xlf:indexqueueworker_description',
        'value' => IndexQueueWorkerTask::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'iconOverlay' => 'content-clock',
        'group' => 'scheduler',
    ],
    '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
        --div--;core.form.tabs:timing,
            --palette--;;execution,
        --div--;core.form.tabs:access,
            disable,
        --div--;core.form.tabs:extended,',
    [],
    '',
    'tx_scheduler_task',
);
