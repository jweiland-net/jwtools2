<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Hooks;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectPostInitHookInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Hook to initialize stdWrap
 */
class InitializeStdWrap implements ContentObjectPostInitHookInterface
{
    /**
     * Hook for post processing the initialization of ContentObjectRenderer
     */
    public function postProcessContentObjectInitialization(ContentObjectRenderer &$parentObject): void
    {
        $extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('jwtools2');
        if ($extConf['typo3TransferTypoScriptCurrent']) {
            // parentRecord is filled in CONTENT and RECORD context only. So no further checks needed
            if (is_array($parentObject->parentRecord) && !empty($parentObject->parentRecord)) {
                // set current to value of parent current
                $parentObject->data[$parentObject->currentValKey] = $parentObject->parentRecord['data'][$parentObject->currentValKey];
            }
        }
    }
}
