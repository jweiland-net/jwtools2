<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Backend;

use TYPO3\CMS\Backend\Template\ModuleTemplate;
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
    protected ?UriBuilder $uriBuilder = null;
    protected ?IconFactory $iconFactory = null;
    protected ?ModuleTemplate $view = null;
    protected ?Request $request = null;

    public function __construct(Request $request, ModuleTemplate $view)
    {
        $this->request = $request;
        $this->view = $view;

        if ($this->iconFactory === null) {
            $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        }
    }

    public function injectUriBuilder(UriBuilder $uriBuilder): void
    {
        $this->uriBuilder = $uriBuilder;
    }

    public function injectIconFactory(IconFactory $iconFactory): void
    {
        $this->iconFactory = $iconFactory;
    }

    protected function addShortcutButton(): void
    {
        $buttonBar = $this->view
            ->getDocHeaderComponent()
            ->getButtonBar();

        $shortcutButton = $buttonBar
            ->makeShortcutButton()
            ->setRouteIdentifier($this->request->getPluginName())
            ->setDisplayName('Jwtools2');

        $buttonBar->addButton($shortcutButton);
    }

    protected function addCloseButton(): void
    {
        $buttonBar = $this->view
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
