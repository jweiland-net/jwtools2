<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Service;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class PlaceholderImageService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private const string FALLBACK_FILE_NAME = 'placeholder_fallback.png';

    private const array PLACEHOLDER_FILE_EXTENSIONS = ['png', 'jpg', 'jpeg', 'gif', 'webp'];

    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly RequestFactory         $requestFactory,
        private readonly ResourceFactory        $resourceFactory,
    )
    {
    }

    public function getPlaceholderFile(int $width, int $height): ?File
    {
        $folder = $this->getCacheFolder();
        if (!$folder instanceof Folder) {
            return null;
        }

        $width = max(1, $width);
        $height = max(1, $height);

        $existingFile = $this->getExistingPlaceholderFile($folder, $width, $height);
        if ($existingFile instanceof File) {
            return $existingFile;
        }

        return $this->fetchAndCache($folder, $width, $height);
    }

    protected function getExistingPlaceholderFile(Folder $folder, int $width, int $height): ?File
    {
        foreach (self::PLACEHOLDER_FILE_EXTENSIONS as $extension) {
            $existingFile = $this->getExistingFile($folder, sprintf('placeholder_%dx%d.%s', $width, $height, $extension));
            if ($existingFile instanceof File) {
                return $existingFile;
            }
        }

        return null;
    }

    protected function fetchAndCache(Folder $folder, int $width, int $height): ?File
    {
        $apiUrl = $this->buildApiUrl($width, $height);
        if ($apiUrl === null) {
            return $this->getFallbackFile($folder);
        }

        try {
            $response = $this->requestFactory->request($apiUrl);
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException(
                    'Placeholder image API returned status ' . $response->getStatusCode(),
                    1732000001,
                );
            }

            $content = $response->getBody()->getContents();
            $extension = $this->getExtensionForContentType($response->getHeaderLine('Content-Type'));
            if ($extension === null) {
                throw new \RuntimeException(
                    'Placeholder image API returned a non-rasterizable content type: ' . $response->getHeaderLine('Content-Type'),
                    1732000002,
                );
            }
        } catch (\Throwable $exception) {
            $this->logger?->warning(
                'Could not fetch placeholder image from API, falling back to bundled placeholder',
                ['exception' => $exception, 'apiUrl' => $apiUrl],
            );

            return $this->getFallbackFile($folder);
        }

        $fileName = sprintf('placeholder_%dx%d.%s', $width, $height, $extension);
        $storedFile = $this->storeContentAsFile($folder, $fileName, $content);

        return $storedFile ?? $this->getFallbackFile($folder);
    }

    protected function getExtensionForContentType(string $contentType): ?string
    {
        return match (strtolower(trim(explode(';', $contentType, 2)[0]))) {
            'image/png' => 'png',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => null,
        };
    }

    protected function storeContentAsFile(Folder $folder, string $fileName, string $content): ?File
    {
        $temporaryFile = GeneralUtility::tempnam('jwtools2_placeholder_', '.' . pathinfo($fileName, PATHINFO_EXTENSION));
        if (!GeneralUtility::writeFile($temporaryFile, $content)) {
            return null;
        }

        try {
            $file = $folder->addFile($temporaryFile, $fileName, DuplicationBehavior::REPLACE);
        } catch (\Throwable $exception) {
            $this->logger?->warning(
                'Could not store downloaded placeholder image in FAL',
                ['exception' => $exception, 'fileName' => $fileName],
            );

            return null;
        } finally {
            if (is_file($temporaryFile)) {
                unlink($temporaryFile);
            }
        }

        return $file instanceof File ? $file : null;
    }

    protected function getFallbackFile(Folder $folder): ?File
    {
        $existingFile = $this->getExistingFile($folder, self::FALLBACK_FILE_NAME);
        if ($existingFile instanceof File) {
            return $existingFile;
        }

        $bundledPath = GeneralUtility::getFileAbsFileName(
            'EXT:jwtools2/Resources/Public/Images/MetaDataPlaceholder.png',
        );
        if (!is_file($bundledPath)) {
            return null;
        }

        try {
            $file = $folder->addFile($bundledPath, self::FALLBACK_FILE_NAME, DuplicationBehavior::REPLACE);
        } catch (\Throwable $exception) {
            $this->logger?->warning(
                'Could not store bundled fallback placeholder image in FAL',
                ['exception' => $exception],
            );

            return null;
        }

        return $file instanceof File ? $file : null;
    }

    protected function getExistingFile(Folder $folder, string $fileName): ?File
    {
        if (!$folder->hasFile($fileName)) {
            return null;
        }

        $file = $folder->getFile($fileName);

        return $file instanceof File ? $file : null;
    }

    protected function buildApiUrl(int $width, int $height): ?string
    {
        $template = trim($this->getConfigurationValue('typo3PlaceholderImageApiUrl'));
        if ($template === '') {
            return null;
        }

        return str_replace(['{width}', '{height}'], [(string)$width, (string)$height], $template);
    }

    protected function getCacheFolder(): ?Folder
    {
        $identifier = trim($this->getConfigurationValue('typo3PlaceholderImageStorageFolder'));
        if ($identifier === '') {
            return null;
        }

        $parts = explode(':', $identifier, 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        [$storageUid, $folderPath] = $parts;

        try {
            $storage = $this->resourceFactory->getStorageObject((int)$storageUid);
        } catch (\Throwable $exception) {
            $this->logger?->warning(
                'Configured placeholder image storage does not exist',
                ['exception' => $exception, 'identifier' => $identifier],
            );

            return null;
        }

        if ($storage->hasFolder($folderPath)) {
            return $storage->getFolder($folderPath);
        }

        try {
            return $storage->createFolder($folderPath);
        } catch (\Throwable $exception) {
            $this->logger?->warning(
                'Could not create placeholder image cache folder',
                ['exception' => $exception, 'identifier' => $identifier],
            );

            return null;
        }
    }

    protected function getConfigurationValue(string $key): string
    {
        try {
            return (string)$this->extensionConfiguration->get('jwtools2', $key);
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
            return '';
        }
    }
}
