<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Backend;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * DocHeader for our Solr Module
 */
class SolrDocHeader extends AbstractDocHeader
{
    public function renderDocHeader(): void
    {
        // initialize UriBuilder
        if (!($this->uriBuilder instanceof UriBuilder)) {
            $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        }
        $this->uriBuilder->setRequest($this->request);

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

        $splitButtonBar = $buttonBar
            ->makeSplitButton();

        $newButton = $buttonBar
            ->makeInputButton()
            ->setName('module')
            ->setValue('solr')
            //->setOnClick()
            ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL))
            ->setTitle('Solr Overview')
            ->setShowLabelText(true)
            ->setDataAttributes([
                'dispatch-action' => 'TYPO3.WindowManager.localOpen',
                // JSON encoded representation of JavaScript function arguments
                // (HTML attributes are encoded in \TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton)
                'dispatch-args' => GeneralUtility::jsonEncodeForHtmlAttribute([
                    $this->getLinkForUrl($this->uriBuilder->reset()->uriFor('list', [], 'Solr')),
                ], false)
            ]);

        $splitButtonBar->addItem($newButton, true);

        $newButton = $buttonBar
            ->makeInputButton()
            ->setName('module')
            ->setValue('cleanUp')
            //->setOnClick($this->getLinkForUrl($this->uriBuilder->reset()->uriFor('showClearFullIndexForm', [], 'Solr')))
            ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL))
            ->setTitle('Clear full index...')
            ->setShowLabelText(true);
        $splitButtonBar->addItem($newButton);

        $buttonBar->addButton($splitButtonBar);
    }
}
