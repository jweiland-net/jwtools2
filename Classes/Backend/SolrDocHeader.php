<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Backend;

use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * DocHeader for our Solr Module
 */
class SolrDocHeader
{
    public function __construct(
        private readonly Request $request,
        private readonly ModuleTemplate $view,
        private readonly IconFactory $iconFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly ComponentFactory $componentFactory,
    ) {}

    public function renderDocHeader(): void
    {
        // Render Buttons
        $this->addShortcutButton();
        $this->addCloseButton();
        $this->addModuleSelector();
    }

    protected function addModuleSelector(): void
    {
        $buttonBar = $this->view
            ->getDocHeaderComponent()
            ->getButtonBar();

        $overviewButton = $this->componentFactory
            ->createLinkButton()
            ->setHref($this->uriBuilder->reset()->uriFor('list', [], 'Solr'))
            ->setIcon($this->iconFactory->getIcon('actions-document-new', IconSize::SMALL))
            ->setTitle('Overview')
            ->setShowLabelText(true);

        $clearFullIndexButton = $this->componentFactory
            ->createLinkButton()
            ->setHref($this->uriBuilder->reset()->uriFor('showClearFullIndexForm', [], 'Solr'))
            ->setIcon($this->iconFactory->getIcon('actions-document-new', IconSize::SMALL))
            ->setTitle('Clear full index...')
            ->setShowLabelText(true);

        $buttonBar
            ->addButton($overviewButton)
            ->addButton($clearFullIndexButton);
    }

    protected function addShortcutButton(): void
    {
        $buttonBar = $this->view
            ->getDocHeaderComponent()
            ->getButtonBar();

        $shortcutButton = $this->componentFactory
            ->createShortcutButton()
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

        $closeButton = $this->componentFactory
            ->createLinkButton()
            ->setHref($uri)
            ->setIcon($this->iconFactory->getIcon('actions-close', IconSize::SMALL))
            ->setTitle('Close');

        $buttonBar->addButton($closeButton);
    }
}
