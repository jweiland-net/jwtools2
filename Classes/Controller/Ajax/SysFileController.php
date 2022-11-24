<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Controller\Ajax;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Resource\Event\AfterFileAddedEvent;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\Processing\FileDeletionAspect;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\CommandUtility;
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

    /**
     * @var GraphicalFunctions
     */
    protected $graphicalFunctions;

    public function __construct(ResourceFactory $resourceFactory, GraphicalFunctions $graphicalFunctions)
    {
        $this->resourceFactory = $resourceFactory;
        $this->graphicalFunctions = $graphicalFunctions;
    }

    public function updateFileMetadataAction(ServerRequestInterface $request): JsonResponse
    {
        foreach ($this->getValidatedFiles($request) as $combinedIdentifier) {
            $fileObject = $this->resourceFactory->getFileObjectFromCombinedIdentifier($combinedIdentifier);
            if ($fileObject instanceof FileInterface && $fileObject->exists()) {
                $this->cleanupProcessedFilesForFile($fileObject);
                $this->updateExifData($fileObject);
                $indexer = $this->getIndexer($fileObject->getStorage());
                // Do not use "extractMetaData" as this will only process the OnlineExtractor, but does
                // not update the image width/height
                $indexer->updateIndexEntry($fileObject);
            }
        }

        return (new JsonResponse())->setPayload([]);
    }

    /**
     * In imagemagick prior version 6.6 EXIF data was not written correctly. It may happen that
     * width/height of EXIF differs from original width/height.
     * This is an important step for further file extractors registered in TYPO3 like EXT:solr and/or EXT:tika
     * which does not read the original image dimensions, but width/height from EXIF data instead.
     *
     * @param FileInterface $fileObject
     */
    protected function updateExifData(FileInterface $fileObject): void
    {
        // Only update EXIF width/height, if we have an updated imagemagick version:
        // https://stackoverflow.com/questions/5840437/resizing-images-without-losing-exif-data
        if (version_compare($this->determineImageMagickVersion(), '6.6.9', '>=')) {
            $imageDimensions = $this->getWidthAndHeightOfFile($fileObject);
            $width = $imageDimensions['width'] ?? 0;
            $height = $imageDimensions['height'] ?? 0;
            if ($width > 0 && $height > 0) {
                $result = $this->graphicalFunctions->imageMagickConvert(
                    $fileObject->getForLocalProcessing(),
                    '', // keep current ext
                    (string)$width,
                    (string)$height,
                    '-colorspace RGB -quality 100', // Do not reduce quality. It's the original image
                    '', // keep default
                    ['noScale' => true], // keep default
                    true // As width/height are the same, we have to force creating a new image
                );
                if (is_array($result) && is_file($result[3])) {
                    $fileObject->getStorage()->replaceFile($fileObject, $result[3]);
                }
            }
        }
    }

    /**
     * This method reads the original width/height of the file.
     * It does not relate to EXIF data
     *
     * @param FileInterface $fileObject
     * @return array
     */
    protected function getWidthAndHeightOfFile(FileInterface $fileObject): array
    {
        $metaData = [];
        if ($fileObject->isImage() && $fileObject->getStorage()->getDriverType() === 'Local') {
            $rawFileLocation = $fileObject->getForLocalProcessing(false);
            $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $rawFileLocation);
            $metaData = [
                'width' => $imageInfo->getWidth(),
                'height' => $imageInfo->getHeight(),
            ];
        }

        return $metaData;
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

    protected function determineImageMagickVersion(): string
    {
        $command = CommandUtility::imageMagickCommand('identify', '-version');
        CommandUtility::exec($command, $result);
        $string = $result[0];

        // A version like 6.9.10-23
        $version = '';
        if (!empty($string)) {
            [, $version] = explode('Magick', $string);
            [$version] = explode(' ', trim($version));
            [$version] = explode('-', trim($version));
            $version = trim($version);
        }

        return $version;
    }

    protected function getIndexer(ResourceStorage $storage): Indexer
    {
        return GeneralUtility::makeInstance(Indexer::class, $storage);
    }

    protected function cleanupProcessedFilesForFile(FileInterface $fileObject): void
    {
        $fileDeletionAspect = GeneralUtility::makeInstance(FileDeletionAspect::class);

        $event = new AfterFileAddedEvent(
            $fileObject,
            new Folder(
                $fileObject->getStorage(),
                $fileObject->getParentFolder()->getIdentifier(),
                $fileObject->getParentFolder()->getName()
            )
        );
        $fileDeletionAspect->cleanupProcessedFilesPostFileAdd($event);
    }
}
