<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\ContextMenu\ItemProviders;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider;

/**
 * Adds a new entry ``Create/Update file metadata`` into context menu of filelist module to create a missing file
 * metadata record or to update the existing metadata record (sys_file_metadata).
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
            'callbackAction' => 'updateFileMetadata',
        ],
    ];

    /**
     * Priority must be higher than and different from 100 (FileProvider/AbstractProvider).
     * We don't want to replace FileProvider, we want to add further items to FileProvider.
     */
    public function getPriority(): int
    {
        return 101;
    }

    /**
     * Checks whether certain item can be rendered (e.g. check for disabled items or permissions)
     */
    protected function canRender(string $itemName, string $type): bool
    {
        if (in_array($itemName, $this->disabledItems, true)) {
            return false;
        }

        return $this->canUpdateFile();
    }

    /**
     * Checks if the file (sys_file) is of type image
     */
    protected function canUpdateFile(): bool
    {
        if ($this->record instanceof File) {
            // Do not use $this->record->isImage() as this is also true for SVG and PDF
            return $this->record->getType() === $this->record::FILETYPE_IMAGE;
        }

        return false;
    }

    /**
     * As we can't extend ContextMenuActions.js of TYPO3 Core we have to use our own JS module.
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        return [
            'data-callback-module' => '@jweiland/jwtools2/context-menu-actions',
            'data-status-title' => $this->languageService->sL(
                'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:statusDeleteTitle'
            ),
            'data-status-description' => $this->languageService->sL(
                'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:statusDeleteDescription'
            ),
        ];
    }
}
