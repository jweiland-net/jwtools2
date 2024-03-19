<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\LinkHandler;

use TYPO3\CMS\Backend\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Filelist\LinkHandler\AbstractResourceLinkHandler;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * This file LinkHandler overwrites the LinkHandler of TYPO3 and moves upload and create folder fields to top
 */
class FileLinkHandler extends \TYPO3\CMS\Filelist\LinkHandler\FileLinkHandler
{
    protected ViewInterface $view;

    public function initialize(
        AbstractLinkBrowserController $linkBrowser,
        $identifier,
        array $configuration
    ): void {
        parent::initialize($linkBrowser, $identifier, $configuration);
    }

    /**
     * The parent::setView() method was implemented with TYPO3 11.2.
     * For earlier TYPO3 version we still need the approach from above
     */
    public function setView(ViewInterface $view): AbstractResourceLinkHandler
    {
        $this->addTemplatePath($view);

        return parent::setView($view);
    }

    protected function addTemplatePath(?ViewInterface $view): void
    {
        if ($view instanceof ViewInterface) {
            //$templateRootPaths = ['EXT:recordlist/Resources/Private/Templates/LinkBrowser'];
            //$templateRootPaths[] = 'EXT:jwtools2/Resources/Private/Extensions/Recordlist/Templates';
            //debug($view->getCurrentRenderingContext());
            //setTemplateRootPaths($templateRootPaths);
        }
    }
}
