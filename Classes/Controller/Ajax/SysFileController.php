<?php

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Controller\Ajax;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\Processing\FileDeletionAspect;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Ajax controller to create/update sys_file_metadata records
 */
class SysFileController
{
    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    public function __construct(ResourceFactory $resourceFactory = null)
    {
        $this->resourceFactory = $resourceFactory ?? GeneralUtility::makeInstance(ResourceFactory::class);
    }

    public function updateFileMetadataAction(ServerRequestInterface $request): JsonResponse
    {
        foreach ($this->getValidatedFiles($request) as $combinedIdentifier) {
            $fileObject = $this->resourceFactory->getFileObjectFromCombinedIdentifier($combinedIdentifier);
            if ($fileObject instanceof FileInterface && $fileObject->exists()) {
                $this->cleanupProcessedFilesForFile($fileObject);
                $indexer = $this->getIndexer($fileObject->getStorage());
                // Do not use "extractMetaData" as this will only process the OnlineExtractor, but does
                // not update the image width/height
                $indexer->updateIndexEntry($fileObject);
            }
        }

        return (new JsonResponse())->setPayload([]);
    }

    protected function getValidatedFiles(ServerRequestInterface $request): array
    {
        $validatedFiles = [];
        $files = $request->getQueryParams()['CB']['files'] ?? [];
        foreach ($files as $hash => $file) {
            [$table, $hash] = explode('|', $hash);
            if ($table === '_FILE' && $hash === (string)substr(md5($file), 0, 10)) {
                $validatedFiles[] = $file;
            }
        }

        return $validatedFiles;
    }

    protected function getIndexer(ResourceStorage $storage): Indexer
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }

    protected function cleanupProcessedFilesForFile(FileInterface $fileObject): void
    {
        $fileDeletionAspect = GeneralUtility::makeInstance(FileDeletionAspect::class);
        if (version_compare(TYPO3_branch, '10.0', '>=')) {
            $event = new AfterFileAddedEvent(
                $fileObject,
                new Folder($fileObject->getStorage(), $fileObject->getParentFolder()->getIdentifier(), $fileObject->getParentFolder()->getName())
            );
            $fileDeletionAspect->cleanupProcessedFilesPostFileAdd($event);
        } else {
            $fileDeletionAspect->cleanupProcessedFilesPostFileAdd($fileObject, '');
        }
    }
}
