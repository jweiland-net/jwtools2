<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\EventListener;

use JWeiland\Jwtools2\Service\FileMetaDataValidationService;
use JWeiland\Jwtools2\Service\PlaceholderImageService;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Resource\Event\BeforeFileProcessingEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileType;

final readonly class ReplaceInvalidMetaDataImageEventListener
{
    private const string PLACEHOLDER_FILE_NAME_PREFIX = 'placeholder_';

    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private FileMetaDataValidationService $fileMetaDataValidationService,
        private PlaceholderImageService $placeholderImageService,
    ) {}

    public function __invoke(BeforeFileProcessingEvent $event): void
    {
        if (!$this->isEnabled() || !$this->isFrontendRequest()) {
            return;
        }

        $file = $event->getFile();
        if (!$file instanceof File || $file->getType() !== FileType::IMAGE->value) {
            return;
        }

        if ($this->isPlaceholderFile($file)) {
            return;
        }

        if ($this->fileMetaDataValidationService->hasValidMetaData($file)) {
            return;
        }

        $configuration = $event->getConfiguration();
        $placeholder = $this->placeholderImageService->getPlaceholderFile(
            $this->extractDimension($configuration['width'] ?? null, 300),
            $this->extractDimension($configuration['height'] ?? null, 300),
        );

        if (!$placeholder instanceof File) {
            return;
        }

        try {
            $processedFile = $placeholder->process($event->getTaskType(), $configuration);
        } catch (\Throwable) {
            return;
        }

        $event->setProcessedFile($processedFile);
    }

    private function isPlaceholderFile(File $file): bool
    {
        return str_starts_with($file->getName(), self::PLACEHOLDER_FILE_NAME_PREFIX);
    }

    private function extractDimension(mixed $value, int $default): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $dimension = (int)$value;

        return $dimension > 0 ? $dimension : $default;
    }

    private function isFrontendRequest(): bool
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;

        return $request instanceof ServerRequestInterface
            && ApplicationType::fromRequest($request)->isFrontend();
    }

    private function isEnabled(): bool
    {
        try {
            return (bool)$this->extensionConfiguration->get(
                'jwtools2',
                'typo3ReplaceFrontendImagesWithInvalidMetaData',
            );
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
            return false;
        }
    }
}
