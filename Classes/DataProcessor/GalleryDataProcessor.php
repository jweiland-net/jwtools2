<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\DataProcessor;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use TYPO3\CMS\Frontend\DataProcessing\GalleryProcessor;

final readonly class GalleryDataProcessor implements DataProcessorInterface
{
    public function __construct(
        private GalleryProcessor $originalGalleryProcessor,
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

    /**
     * @throws ContentRenderingException
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        $filesProcessedDataKey = (string)$cObj->stdWrapValue(
            'filesProcessedDataKey',
            $processorConfiguration,
            'files'
        );

        if (isset($processedData[$filesProcessedDataKey]) && is_array($processedData[$filesProcessedDataKey])) {
            /** @var FileReference[] $fileObjects */
            $fileObjects = $processedData[$filesProcessedDataKey];
        } else {
            throw new ContentRenderingException(
                'No files found for key ' . $filesProcessedDataKey . ' in $processedData.',
                1779280032,
            );
        }

        $requiredColumns = $this->getRequiredColumns();

        if ($requiredColumns !== []) {
            foreach ($fileObjects as $key => $fileObject) {
                if (!$this->isValidFileObject($fileObject, $requiredColumns)) {
                    unset($fileObjects[$key]);
                }
            }

            $processedData[$filesProcessedDataKey] = $fileObjects;
        }

        return $this->originalGalleryProcessor->process(
            $cObj,
            $contentObjectConfiguration,
            $processorConfiguration,
            $processedData,
        );
    }

    private function isValidFileObject(FileReference $fileObject, array $requiredColumns): bool
    {
        // Only check image files here
        if ($fileObject->getType() !== 2) {
            return true;
        }

        foreach ($requiredColumns as $requiredColumn) {
            if (!$fileObject->hasProperty($requiredColumn)) {
                return false;
            }

            $value = $fileObject->getProperty($requiredColumn);
            $trimmedValue = is_string($value) ? trim($value) : $value;

            if ($trimmedValue === null || $trimmedValue === '') {
                return false;
            }
        }

        return true;
    }

    private function getRequiredColumns(): array
    {
        $requiredColumns = [];

        try {
            $requiredColumns = GeneralUtility::trimExplode(
                ',',
                $this->extensionConfiguration->get('jwtools2', 'typo3RequiredColumnsForFiles'),
                true,
            );
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
        }

        return $requiredColumns;
    }
}
