<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\ContextMenu\ItemProviders;

use TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider;

/**
 * A command to execute extension updates realized with class.ext_update.php
 */
class UpdateFileMetaDataProvider extends FileProvider
{
    /**
     * @var array
     */
    protected $itemsConfiguration = [
        'update' => [
            'label' => 'Update/Create Metadata',
            'iconIdentifier' => 'actions-page-open',
            'callbackAction' => 'updateFileMetadata'
        ],
    ];

    /**
     * Priority must be higher than and different from 100 (FileProvider/AbstractProvider).
     * We don't want to replace FileProvider, we want to add further items to FileProvider.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 101;
    }

    /**
     * Checks whether certain item can be rendered (e.g. check for disabled items or permissions)
     *
     * @param string $itemName
     * @param string $type
     * @return bool
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }

        return true;
    }

    /**
     * As we can't extend ContextMenuActions.js of TYPO3 Core we have to use our own JS module.
     *
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        return [
            'data-callback-module' => 'TYPO3/CMS/Jwtools2/ContextMenuActions',
            'data-status-title' => $this->languageService->sL('LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:statusDeleteTitle'),
            'data-status-description' => $this->languageService->sL('LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:statusDeleteDescription')
        ];
    }
}
