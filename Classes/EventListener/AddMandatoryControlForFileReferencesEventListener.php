<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\EventListener;

use TYPO3\CMS\Backend\Form\Event\ModifyFileReferenceControlsEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

#[AsEventListener(
    identifier: 'jwtools2/addMandatoryControlForFileReferences',
)]
final readonly class AddMandatoryControlForFileReferencesEventListener
{
    public function __construct(
        private IconFactory $iconFactory,
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function __invoke(ModifyFileReferenceControlsEvent $event): void
    {
        $record = $event->getRecord();
        $requiredColumns = $this->getRequiredColumns();
        if ($requiredColumns === []) {
            return;
        }

        // Only check image files here
        if ((int)($record['type'] ?? 0) !== 2) {
            return;
        }

        foreach ($requiredColumns as $requiredColumn) {
            $value = $record['uid_local'][0][$requiredColumn] ?? $record[$requiredColumn] ?? '';
            $trimmedValue = is_string($value) ? trim($value) : $value;

            if ($trimmedValue === null || $trimmedValue === '') {
                $event->setControl(
                    'requiredColumns',
                    '<button type="button" class="btn btn-default" title="' . $this->getMandatoryMessage($requiredColumns) . '">
                        ' . $this->iconFactory->getIcon('status-dialog-error', IconSize::SMALL)->render() . '
                    </button>
                ');
                return;
            }
        }
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

    protected function getMandatoryMessage(array $requiredColumns): string
    {
        return LocalizationUtility::translate(
            'LLL:EXT:jwtools2/Resources/Private/Language/locallang_mod.xlf:warning.requiredColumns',
            'Jwtools2',
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
            if ($translatedLabel === '') {
                continue;
            }

            $requiredColumns[$key] = $translatedLabel;
        }

        return $requiredColumns;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
