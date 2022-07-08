<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Recordlist\Browser;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\View\FolderUtilityRenderer;

/**
 * JW/SF: move $uploadFiles before $filelist
 */
class FileBrowser extends \TYPO3\CMS\Recordlist\Browser\FileBrowser
{
    /**
     * Overwrite the original rendering of TYPO3 with our own implementation.
     * This is a slightly copy of the original TYPO3 FileBrowser.
     *
     * @return string HTML content
     */
    public function render(): string
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
            foreach ($storages as $storage) {
                $storage->addFileAndFolderNameFilter([$filterObject, 'filterFileList']);
            }
        }
        if ($this->expandFolder) {
            $fileOrFolderObject = null;

            // Try to fetch the folder the user had open the last time he browsed files
            // Fallback to the default folder in case the last used folder is not existing
            try {
                $fileOrFolderObject = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($this->expandFolder);
            } catch (Exception $accessException) {
                // We're just catching the exception here, nothing to be done if folder does not exist or is not accessible.
            } catch (\InvalidArgumentException $driverMissingException) {
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
                if (($defaultUploadFolder = $backendUser->getDefaultUploadFolder($pid, $table, $field)) instanceof FolderInterface) {
                    $this->selectedFolder = $defaultUploadFolder;
                }
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
            $_MOD_SETTINGS = BackendUtility::getModuleData($_MOD_MENU, GeneralUtility::_GP('SET'), 'file_list');
        }
        $displayThumbs = $_MOD_SETTINGS['displayThumbs'] ?? true;
        $noThumbs = $noThumbs ?: !$displayThumbs;
        if ($this->selectedFolder) {
            $files = $this->renderFilesInFolder($this->selectedFolder, $allowedFileExtensions, $noThumbs);
        } else {
            $files = '';
        }
        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);

        $this->setBodyTagParameters();
        $this->moduleTemplate->setTitle($this->getLanguageService()->getLL('fileSelector'));
        $view = $this->moduleTemplate->getView();
        $view->assignMultiple([
            'treeEnabled' => true,
            'treeType' => 'folder',
            'activeFolder' => $this->selectedFolder,
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'content' => $uploadForm . $createFolder . $files, // SF/JW: This is the only change line
            'contentOnly' => $contentOnly,
        ]);
        if ($contentOnly) {
            return $view->render();
        }
        return $this->moduleTemplate->renderContent();
    }
}
