<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Backend;

use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Abstract DocHeader for Backend Modules
 */
abstract class AbstractDocHeader
{
    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * The current request.
     *
     * @var Request
     */
    protected $request;

    public function __construct(Request $request, ViewInterface $view)
    {
        $this->request = $request;
        $this->view = $view;
    }

    public function injectUriBuilder(UriBuilder $uriBuilder): void
    {
        $this->uriBuilder = $uriBuilder;
    }

    public function injectIconFactory(IconFactory $iconFactory): void
    {
        $this->iconFactory = $iconFactory;
    }

    protected function addHelpButton(): void
    {
        $buttonBar = $this->view->getModuleTemplate()
            ->getDocHeaderComponent()
            ->getButtonBar();

        $cshButton = $buttonBar
            ->makeHelpButton()
            ->setModuleName('_MOD_' . 'tools_Jwtools2tools')
            ->setFieldName('');

        $buttonBar->addButton($cshButton);
    }

    protected function addShortcutButton(): void
    {
        $buttonBar = $this->view
            ->getModuleTemplate()
            ->getDocHeaderComponent()
            ->getButtonBar();

        $shortcutButton = $buttonBar
            ->makeShortcutButton()
            ->setModuleName(
                $this->request->getPluginName()
            );

        $buttonBar->addButton($shortcutButton);
    }

    protected function addCloseButton(): void
    {
        $buttonBar = $this->view
            ->getModuleTemplate()
            ->getDocHeaderComponent()
            ->getButtonBar();

        $uri = $this->uriBuilder
            ->reset()
            ->uriFor('overview', [], 'Tools');

        $closeButton = $buttonBar
            ->makeLinkButton()
            ->setHref($uri)
            ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL))
            ->setTitle('Close');

        $buttonBar->addButton($closeButton);
    }

    /**
     * Get Link to create new configuration records of defined type
     */
    protected function getLinkForUrl(string $url): string
    {
        return 'window.location.href=' . GeneralUtility::quoteJSvalue($url) . '; return false;';
    }
}
