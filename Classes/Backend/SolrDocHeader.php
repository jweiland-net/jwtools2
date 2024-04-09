<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Backend;


use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
        private readonly UriBuilder $uriBuilder
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

        $overviewButton = $buttonBar
            ->makeLinkButton()
            ->setHref($this->uriBuilder->reset()->uriFor('list', [], 'Solr'))
            ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL))
            ->setTitle('Overview')
            ->setShowLabelText(true);

        $clearFullIndexButton = $buttonBar
            ->makeLinkButton()
            ->setHref($this->uriBuilder->reset()->uriFor('showClearFullIndexForm', [], 'Solr'))
            ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL))
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
