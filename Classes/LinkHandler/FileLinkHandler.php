<?php

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\LinkHandler;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;

/**
 * Modified version of TYPO3's FileLinkHandler.
 * We have modified the templates to allow showing the upload form on top of the file list
 */
class FileLinkHandler extends \TYPO3\CMS\Recordlist\LinkHandler\FileLinkHandler
{
    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration): void
    {
        parent::initialize($linkBrowser, $identifier, $configuration);

        // Override template paths
        $this->view->setTemplateRootPaths(
            [
                GeneralUtility::getFileAbsFileName(
                    'EXT:recordlist/Resources/Private/Templates/LinkBrowser'
                ),
                GeneralUtility::getFileAbsFileName(
                    'EXT:jwtools2/Resources/Private/Extensions/Recordlist/Templates'
                ),
            ]
        );
    }
}
