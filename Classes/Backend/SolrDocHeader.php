<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Backend;

use TYPO3\CMS\Core\Imaging\Icon;

/**
 * DocHeader for our Solr Module
 */
class SolrDocHeader extends AbstractDocHeader
{
    public function renderDocHeader(): void
    {
        // initialize UriBuilder
        $this->uriBuilder->setRequest($this->request);

        // Render Buttons
        $this->addHelpButton();
        $this->addShortcutButton();
        $this->addCloseButton();
        $this->addModuleSelector();
    }

    protected function addModuleSelector(): void
    {
        $buttonBar = $this->view
            ->getModuleTemplate()
            ->getDocHeaderComponent()
            ->getButtonBar();

        $splitButtonBar = $buttonBar
            ->makeSplitButton();

        $newButton = $buttonBar
            ->makeInputButton()
            ->setName('module')
            ->setValue('solr')
            ->setOnClick($this->getLinkForUrl($this->uriBuilder->reset()->uriFor('list', [], 'Solr')))
            ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL))
            ->setTitle('Solr Overview')
            ->setShowLabelText(true);
        $splitButtonBar->addItem($newButton, true);

        $newButton = $buttonBar
            ->makeInputButton()
            ->setName('module')
            ->setValue('cleanUp')
            ->setOnClick($this->getLinkForUrl($this->uriBuilder->reset()->uriFor('showClearFullIndexForm', [], 'Solr')))
            ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL))
            ->setTitle('Clear full index...')
            ->setShowLabelText(true);
        $splitButtonBar->addItem($newButton, false);

        $buttonBar->addButton($splitButtonBar);
    }
}
