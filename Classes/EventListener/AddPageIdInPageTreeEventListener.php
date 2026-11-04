<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\EventListener;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\BeforeLoadedUserTsConfigEvent;

/**
 * Show the page UID in front of the page title in the page tree, if "typo3EnableUidInPageTree" is activated.
 */
final readonly class AddPageIdInPageTreeEventListener
{
    private const string USER_TS_CONFIG = 'options.pageTree.showPageIdWithTitle = 1';

    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
    ) {}

    public function __invoke(BeforeLoadedUserTsConfigEvent $event): void
    {
        if ($this->isEnabled()) {
            $event->addTsConfig(self::USER_TS_CONFIG);
        }
    }

    private function isEnabled(): bool
    {
        try {
            return (bool)$this->extensionConfiguration->get('jwtools2', 'typo3EnableUidInPageTree');
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
            return false;
        }
    }
}
