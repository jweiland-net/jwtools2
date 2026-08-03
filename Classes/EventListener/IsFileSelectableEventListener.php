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
use TYPO3\CMS\Backend\ElementBrowser\Event\IsFileSelectableEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Reduce category tree to categories of PIDs within current page tree
 */
final readonly class IsFileSelectableEventListener
{
    public function __construct(
        private FileMetaDataValidationService $fileMetaDataValidationService,
        private FlashMessageService $flashMessageService,
    ) {}

    public function __invoke(IsFileSelectableEvent $event): void
    {
        $requiredColumns = $this->fileMetaDataValidationService->getRequiredColumns();

        if ($requiredColumns === []) {
            return;
        }

        $flashMessageService = $this->flashMessageService;
        $flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $message = $this->getFlashMessageDescription($requiredColumns);

        if (!$this->checkMessageExists($flashMessageQueue, $message)) {
            $this->addFlashMessage($flashMessageQueue, $message);
        }

        if (!$event->getFile() instanceof File) {
            // Do not process folders or processed files
            return;
        }

        if ($this->fileMetaDataValidationService->getMissingColumns($event->getFile()) !== []) {
            $event->denyFileSelection();
        }
    }

    protected function addFlashMessage(FlashMessageQueue $flashMessageQueue, string $message): void
    {
        $flashMessageQueue->addMessage(
            GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                LocalizationUtility::translate(
                    'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:fileBrowser.flashMessage.requiredColumns.title',
                ),
                ContextualFeedbackSeverity::INFO,
            ),
        );
    }

    protected function getFlashMessageDescription(array $requiredColumns): string
    {
        return LocalizationUtility::translate(
            'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:fileBrowser.flashMessage.requiredColumns.description',
            null,
            [
                implode(
                    ', ',
                    $this->getTranslatedColumnNames(
                        $requiredColumns,
                    ),
                ),
            ],
        );
    }

    protected function getTranslatedColumnNames(array $requiredColumns): array
    {
        if (!$this->getLanguageService() instanceof LanguageService) {
            return $requiredColumns;
        }

        foreach ($requiredColumns as $key => $requiredColumn) {
            $label = BackendUtility::getItemLabel('sys_file', $requiredColumn);
            if ($label === '' || $label === null) {
                $label = BackendUtility::getItemLabel('sys_file_metadata', $requiredColumn);
            }

            if ($label === '' || $label === null) {
                continue;
            }

            $translatedLabel = $this->getLanguageService()->sL($label);
            if ($translatedLabel === '' || $translatedLabel === null) {
                continue;
            }

            $requiredColumns[$key] = $translatedLabel;
        }

        return $requiredColumns;
    }

    protected function checkMessageExists(FlashMessageQueue $flashMessageQueue, string $message): bool
    {
        $messageExists = false;
        foreach ($flashMessageQueue as $flashMessage) {
            if ($flashMessage->getMessage() === $message) {
                $messageExists = true;
                break;
            }
        }

        return $messageExists;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
