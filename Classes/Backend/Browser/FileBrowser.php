<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Backend\Browser;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Modified version of TYPO3's ElementBrowserController.
 * We have modified the templates to allow showing the upload form on top of the file/folder list
 */
class FileBrowser extends \TYPO3\CMS\Recordlist\Browser\FileBrowser
{
    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * Return "false", as we don't want to render something.
     * We just want to add some RequireJS
     */
    public function isValid(): bool
    {
        $urlParameters = $this->getUrlParameters([]);

        return isset($urlParameters['mode']) && $urlParameters['mode'] === 'file';
    }

    /**
     * This method must exist, as this method will be checked by method_exists in ElementBrowserController
     */
    public function render(): string
    {
        // h3 is the first header. It's before the three following forms: filelist, uploadForm and createForm
        [$top, $content] = GeneralUtility::trimExplode('<h3>', parent::render(), true, 2);
        $content = '<h3>' . $content;
        [$header, $fileList, $uploadForm, $createForm] = explode('<form', $content);

        return sprintf(
            '%s<form%s<form%s%s<form%s',
            $top,
            $uploadForm,
            $createForm,
            $header,
            $fileList
        );
    }
}
