<?php
namespace JWeiland\Jwtools2\Backend;

/*
 * This file is part of the jwtools2 project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Imaging\Icon;

/**
 * DocHeader for our Solr Module
 */
class SolrDocHeader extends AbstractDocHeader
{
    /**
     * Render DocHeader for View
     *
     * @return void
     */
    public function renderDocHeader()
    {
        // initialize UriBuilder
        $this->uriBuilder->setRequest($this->request);

        // Render Buttons
        $this->addHelpButton();
        $this->addShortcutButton();
        $this->addCloseButton();
        $this->addModuleSelector();
    }

    /**
     * Add module selector
     *
     * @return void
     */
    protected function addModuleSelector()
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
            ->setValue('whatever')
            ->setOnClick($this->getLinkForUrl($this->uriBuilder->reset()->uriFor('createIndexQueueForAllSites', [], 'Solr')))
            ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL))
            ->setTitle('Create index queue entries for all sites')
            ->setShowLabelText(true);
        $splitButtonBar->addItem($newButton, false);

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
