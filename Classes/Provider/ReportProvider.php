<?php

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Provider;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\ExtensionService;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Utility\ListUtility;
use TYPO3\CMS\Reports\ExtendedStatusProviderInterface;
use TYPO3\CMS\Reports\Status;
use TYPO3\CMS\Reports\StatusProviderInterface;

/**
 * Provider for EXT:reports module to show updatable extensions
 */
class ReportProvider implements StatusProviderInterface, ExtendedStatusProviderInterface
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var ListUtility
     */
    protected $listUtility;

    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->listUtility = $this->objectManager->get(ListUtility::class);
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }

    /**
     * Returns the status of updatable TYPO3 extensions
     * Visible in TYPO3 reports module
     *
     * @return Status[]
     */
    public function getStatus(): array
    {
        return [
            'jwtools2' => $this->getUpdatableExtensions()
        ];
    }

    /**
     * Returns the status of updatable TYPO3 extensions
     * Visible in status mail of reports task
     *
     * @return Status[]
     */
    public function getDetailedStatus(): array
    {
        return [
            'jwtools2' => $this->getUpdatableExtensions(true)
        ];
    }

    protected function getUpdatableExtensions(bool $renderForReportMail = false)
    {
        $extensionInformation = $this->listUtility->getAvailableAndInstalledExtensionsWithAdditionalInformation();
        $updatableExtensions = [];
        foreach ($extensionInformation as $extensionKey => $information) {
            if (
                array_key_exists('updateToVersion', $information)
                && array_key_exists('updateAvailable', $information)
                && $information['updateToVersion'] instanceof Extension
                && $information['updateAvailable'] === true
            ) {
                $terObject = $information['updateToVersion'];
                $localVersion = $information['version'];
                $terVersion = $terObject->getVersion();

                $updatableExtensions[] = sprintf(
                    '%s (%s) %s ==> %s',
                    $terObject->getTitle(),
                    $extensionKey,
                    $localVersion,
                    $terVersion
                );
            }
        }

        if ($updatableExtensions === []) {
            $value = 'none';
            $message = 'No TYPO3 extensions found for update. But please update extension list before to be sure.';
        } else {
            $value = sprintf('%d extensions found', count($updatableExtensions));
            if ($renderForReportMail) {
                $message = 'Following TYPO3 extensions are ready for update:' . chr(10);
                foreach ($updatableExtensions as $updatableExtension) {
                    $message .= '* ' . $updatableExtension . chr(10);
                }
            } else {
                $message = 'Following TYPO3 extensions are ready for update:';
                $message .= '<ul>';
                foreach ($updatableExtensions as $updatableExtension) {
                    $message .= '<li>' . $updatableExtension . '</li>';
                }
                $message .= '</ul>';
            }
        }

        return GeneralUtility::makeInstance(
            Status::class,
            'Updatable Extensions',
            $value,
            $message,
            $this->getSeverity($renderForReportMail)
        );
    }

    protected function getSeverity(bool $renderForReportMail): int
    {
        $extConfSeverity = $this->extensionConfiguration->get(
            'jwtools2',
            'sendUpdatableExtensionsWithSeverity'
        );

        // There is no need to render extension updates as WARNING in reports module.
        return $renderForReportMail && $extConfSeverity === 'warning' ? Status::WARNING : Status::INFO;
    }
}
