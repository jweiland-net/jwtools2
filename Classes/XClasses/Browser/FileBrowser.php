<?php

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\XClasses\Browser;

use TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\View\FolderUtilityRenderer;

/**
 * JW/SF: move $uploadFiles before $filelist
 *
 * Browser for filess
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class FileBrowser extends \TYPO3\CMS\Recordlist\Browser\FileBrowser
{
    /**
     * @return string HTML content
     */
    public function render(): string
    {
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Jwtools2/Backend/FileBrowser');

        return parent::render();
    }
}
