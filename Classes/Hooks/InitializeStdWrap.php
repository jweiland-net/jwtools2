<?php

namespace JWeiland\Jwtools2\Hooks;

/*
 * This file is part of the jwtools2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use JWeiland\Jwtools2\Configuration\ExtConf;
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
     *
     * @param ContentObjectRenderer $parentObject Parent content object
     */
    public function postProcessContentObjectInitialization(ContentObjectRenderer &$parentObject)
    {
        /** @var ExtConf $extConf */
        $extConf = GeneralUtility::makeInstance(ExtConf::class);
        if ($extConf->getTypo3TransferTypoScriptCurrent()) {
            // parentRecord is filled in CONTENT and RECORD context only. So no further checks needed
            if (is_array($parentObject->parentRecord) && !empty($parentObject->parentRecord)) {
                // set current to value of parent current
                $parentObject->data[$parentObject->currentValKey] = $parentObject->parentRecord['data'][$parentObject->currentValKey];
            }
        }
    }
}
