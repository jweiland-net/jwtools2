<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Frontend\ContentObject\Event\AfterContentObjectRendererInitializedEvent;

/**
 * Reduce category tree to categories of PIDs within current page tree
 */
#[AsEventListener(
    identifier: 'jwtools2/afterContentObjectRendererInitialized',
)]
final class AfterContentObjectRendererInitializedEventListener
{
    public function __construct(protected readonly ExtensionConfiguration $extensionConfiguration) {}

    public function __invoke(AfterContentObjectRendererInitializedEvent $event): void
    {
        $contentObjectRenderer = $event->getContentObjectRenderer();

        if (
            isset($contentObjectRenderer->parentRecord['data'][$contentObjectRenderer->currentValKey], $contentObjectRenderer->data['colPos']) && is_array(
                $contentObjectRenderer->parentRecord,
            ) && $contentObjectRenderer->parentRecord !== [] && ($this->getConfiguration(
            )['typo3TransferTypoScriptCurrent'] ?? false) && $contentObjectRenderer->data['colPos'] !== 0
        ) {
            $contentObjectRenderer->setCurrentVal(
                $contentObjectRenderer->parentRecord['data'][$contentObjectRenderer->currentValKey],
            );
        }
    }

    protected function getConfiguration(): array
    {
        try {
            return $this->extensionConfiguration->get('jwtools2');
        } catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException $exception) {
            return [];
        }
    }
}
