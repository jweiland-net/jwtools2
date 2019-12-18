<?php
namespace JWeiland\Jwtools2\XClasses\Browser;

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
    public function render()
    {
        $backendUser = $this->getBackendUser();

        // The key number 3 of the bparams contains the "allowed" string. Disallowed is not passed to
        // the element browser at all but only filtered out in DataHandler afterwards
        $allowedFileExtensions = GeneralUtility::trimExplode(',', explode('|', $this->bparams)[3], true);
        if (!empty($allowedFileExtensions) && $allowedFileExtensions[0] !== 'sys_file' && $allowedFileExtensions[0] !== '*') {
            // Create new filter object
            $filterObject = GeneralUtility::makeInstance(FileExtensionFilter::class);
            $filterObject->setAllowedFileExtensions($allowedFileExtensions);
            // Set file extension filters on all storages
            $storages = $backendUser->getFileStorages();
            /** @var \TYPO3\CMS\Core\Resource\ResourceStorage $storage */
            foreach ($storages as $storage) {
                $storage->addFileAndFolderNameFilter([$filterObject, 'filterFileList']);
            }
        }
        if ($this->expandFolder) {
            $fileOrFolderObject = null;

            // Try to fetch the folder the user had open the last time he browsed files
            // Fallback to the default folder in case the last used folder is not existing
            try {
                $fileOrFolderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->expandFolder);
            } catch (Exception $accessException) {
                // We're just catching the exception here, nothing to be done if folder does not exist or is not accessible.
            } catch (\InvalidArgumentException $driverMissingExecption) {
                // We're just catching the exception here, nothing to be done if the driver does not exist anymore.
            }

            if ($fileOrFolderObject instanceof Folder) {
                // It's a folder
                $this->selectedFolder = $fileOrFolderObject;
            } elseif ($fileOrFolderObject instanceof FileInterface) {
                // It's a file
                $this->selectedFolder = $fileOrFolderObject->getParentFolder();
            }
        }
        // Or get the user's default upload folder
        if (!$this->selectedFolder) {
            try {
                [, $pid, $table,, $field] = explode('-', explode('|', $this->bparams)[4]);
                $this->selectedFolder = $backendUser->getDefaultUploadFolder($pid, $table, $field);
            } catch (\Exception $e) {
                // The configured default user folder does not exist
            }
        }
        // Build the file upload and folder creation form
        $uploadForm = '';
        $createFolder = '';
        if ($this->selectedFolder) {
            $folderUtilityRenderer = GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this);
            $uploadForm = $folderUtilityRenderer->uploadForm($this->selectedFolder, $allowedFileExtensions);
            $createFolder = $folderUtilityRenderer->createFolder($this->selectedFolder);
        }

        // Getting flag for showing/not showing thumbnails:
        $noThumbs = $backendUser->getTSConfig()['options.']['noThumbsInEB'] ?? false;
        $_MOD_SETTINGS = [];
        if (!$noThumbs) {
            // MENU-ITEMS, fetching the setting for thumbnails from File>List module:
            $_MOD_MENU = ['displayThumbs' => ''];
            $_MCONF['name'] = 'file_list';
            $_MOD_SETTINGS = BackendUtility::getModuleData($_MOD_MENU, GeneralUtility::_GP('SET'), $_MCONF['name']);
        }
        $displayThumbs = $_MOD_SETTINGS['displayThumbs'] ?? false;
        $noThumbs = $noThumbs ?: !$displayThumbs;
        // Create folder tree:
        /** @var ElementBrowserFolderTreeView $folderTree */
        $folderTree = GeneralUtility::makeInstance(ElementBrowserFolderTreeView::class);
        $folderTree->setLinkParameterProvider($this);
        $tree = $folderTree->getBrowsableTree();
        if ($this->selectedFolder) {
            $files = $this->renderFilesInFolder($this->selectedFolder, $allowedFileExtensions, $noThumbs);
        } else {
            $files = '';
        }

        $this->initDocumentTemplate();
        // Starting content:
        $content = $this->doc->startPage(htmlspecialchars($this->getLanguageService()->getLL('fileSelector')));

        // Putting the parts together, side by side:
        $markup = [];
        $markup[] = '<!-- Wrapper table for folder tree / filelist: -->';
        $markup[] = '<div class="element-browser">';
        $markup[] = '   <div class="element-browser-panel element-browser-main">';
        $markup[] = '       <div class="element-browser-main-sidebar">';
        $markup[] = '           <div class="element-browser-body">';
        $markup[] = '               ' . $tree;
        $markup[] = '           </div>';
        $markup[] = '       </div>';
        $markup[] = '       <div class="element-browser-main-content">';
        $markup[] = '           <div class="element-browser-body">';
        $markup[] = '               ' . $this->doc->getFlashMessages();
        $markup[] = '               ' . $uploadForm;
        $markup[] = '               ' . $createFolder;
        $markup[] = '               ' . $files;
        $markup[] = '           </div>';
        $markup[] = '       </div>';
        $markup[] = '   </div>';
        $markup[] = '</div>';
        $content .= implode('', $markup);

        // Ending page, returning content:
        $content .= $this->doc->endPage();
        return $this->doc->insertStylesAndJS($content);
    }
}
