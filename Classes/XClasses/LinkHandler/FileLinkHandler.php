<?php
namespace JWeiland\Jwtools2\XClasses\LinkHandler;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;

/**
 * Link handler for files
 * @internal This class is a specific LinkHandler implementation and is not part of the TYPO3's Core API.
 */
class FileLinkHandler extends \TYPO3\CMS\Recordlist\LinkHandler\FileLinkHandler
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
