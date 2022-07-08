<?php

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\XClasses\LinkHandler;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;

/**
 * Link handler for folder links
 *
 * @internal This class is a specific LinkHandler implementation and is not part of the TYPO3's Core API.
 */
class FolderLinkHandler extends \TYPO3\CMS\Recordlist\LinkHandler\FolderLinkHandler
{
    /**
     * Initialize the handler
     *
     * @param AbstractLinkBrowserController $linkBrowser
     * @param string $identifier
     * @param array $configuration Page TSconfig
     */
    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration)
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
