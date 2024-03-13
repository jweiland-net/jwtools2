<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Hooks;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectPostInitHookInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Hook to initialize stdWrap
 */
class InitializeStdWrap implements ContentObjectPostInitHookInterface
{
    protected ExtensionConfiguration $extensionConfiguration;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    /**
     * Hook for post-processing the initialization of ContentObjectRenderer
     */
    public function postProcessContentObjectInitialization(ContentObjectRenderer &$parentObject): void
    {
        // parentRecord is filled in CONTENT and RECORD context only. So no further checks needed
        if (
            is_array($parentObject->parentRecord)
            && $parentObject->parentRecord !== []
            && isset($parentObject->parentRecord['data'][$parentObject->currentValKey])
            && ($this->getConfiguration()['typo3TransferTypoScriptCurrent'] ?? false)
        ) {
            // Set current to value of parent current
            if (isset($parentObject->data['colPos']) && $parentObject->data['colPos'] !== 0) {
                $parentObject->data[$parentObject->currentValKey] = $parentObject->parentRecord['data'][$parentObject->currentValKey];
            }
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
