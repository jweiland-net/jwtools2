<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Hooks;

use TYPO3\CMS\Backend\LinkHandler\FileLinkHandler;
use TYPO3\CMS\Backend\LinkHandler\FolderLinkHandler;

/**
 * Hook to modify per TSConfig registered LinkHandlers with our own implementations.
 * Modifies the LinkHandlers for link wizards (just right behind a text field and in RTE).
 * Needed to show upload form above the file/folder list for example.
 */
class ModifyLinkHandlerHook
{
    /**
     * Will be called by AbstractLinkBrowserController.
     * Method name was checked by method_exists()
     */
    public function modifyLinkHandlers(array $linkHandlers, array $currentLinkParts): array
    {
        foreach ($linkHandlers as &$linkHandler) {
            if (!isset($linkHandler['handler'])) {
                continue;
            }

            // Only overwrite TYPO3 core LinkHandlers
            if ($linkHandler['handler'] === FileLinkHandler::class) {
                $linkHandler['handler'] = \JWeiland\Jwtools2\LinkHandler\FileLinkHandler::class;
            }

            if ($linkHandler['handler'] === FolderLinkHandler::class) {
                $linkHandler['handler'] = \JWeiland\Jwtools2\LinkHandler\FolderLinkHandler::class;
            }
        }

        return $linkHandlers;
    }
}
