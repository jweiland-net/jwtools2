<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\XClasses\Browser;

/**
 * JW/SF: move $uploadFiles before $filelist
 *
 * Browser for filess
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class FileBrowser extends \TYPO3\CMS\Recordlist\Browser\FileBrowser
{
    /**
     * TODO: Solve this via $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/browse_links.php']['browserRendering']
     * @return string HTML content
     */
    public function render(): string
    {
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Jwtools2/Backend/FileBrowser');

        return parent::render();
    }
}
