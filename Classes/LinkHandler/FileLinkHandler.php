<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\LinkHandler;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * This file LinkHandler overwrites the LinkHandler of TYPO3 and moves upload and create folder fields to top
 */
class FileLinkHandler extends \TYPO3\CMS\Recordlist\LinkHandler\FileLinkHandler
{
    /**
     * @var StandaloneView|ViewInterface
     */
    protected $view;

    public function initialize(
        AbstractLinkBrowserController $linkBrowser,
        $identifier,
        array $configuration
    ): void {
        parent::initialize($linkBrowser, $identifier, $configuration);

        $this->addTemplatePath($this->view);
    }

    /**
     * The parent::setView() method was implemented with TYPO3 11.2.
     * For earlier TYPO3 version we still need the approach from above
     */
    public function setView(ViewInterface $view): void
    {
        $this->addTemplatePath($view);

        parent::setView($view);
    }

    /**
     * Yes, since TYPO3 11.2 this method will be called twice, but
     * at first call $view (coming from initialize) is empty (not instance of StandaloneView).
     * So, nothing will be changed on first call.
     */
    protected function addTemplatePath(?ViewInterface $view): void
    {
        if ($view instanceof StandaloneView) {
            $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
            $templateRootPaths = ['EXT:recordlist/Resources/Private/Templates/LinkBrowser'];
            if (version_compare($typo3Version->getBranch(), '11.2', '<')) {
                $templateRootPaths[] = 'EXT:jwtools2/Resources/Private/Extensions/Recordlist/Templates/V10';
            } else {
                $templateRootPaths[] = 'EXT:jwtools2/Resources/Private/Extensions/Recordlist/Templates';
            }
            $view->setTemplateRootPaths($templateRootPaths);
        }
    }
}
