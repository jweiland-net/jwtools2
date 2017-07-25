<?php
namespace JWeiland\Jwtools2\Backend;

/*
 * This file is part of the TYPO3 CMS project.
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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class DocHeader
{
    /**
     * @var UriBuilder
     */
    protected $uriBuilder = null;

    /**
     * @var IconFactory
     */
    protected $iconFactory = null;

    /**
     * @var BackendTemplateView
     */
    protected $view = null;

    /**
     * The current request.
     *
     * @var Request
     */
    protected $request = null;

    /**
     * inject uriBuilder
     *
     * @param UriBuilder $uriBuilder
     * @return void
     */
    public function injectUriBuilder(UriBuilder $uriBuilder)
    {
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * inject iconFactory
     *
     * @param IconFactory $iconFactory
     * @return void
     */
    public function injectIconFactory(IconFactory $iconFactory)
    {
        $this->iconFactory = $iconFactory;
    }

    /**
     * Render DocHeader for View
     *
     * @return void
     */
    public function renderDocHeader()
    {
        // initialize UriBuilder
        $this->uriBuilder->setRequest($this->getRequest());

        // Render Buttons
        $this->addHelpButton();
        $this->addShortcutButton();
        $this->addCloseButton();
        $this->addModuleSelector();
    }

    /**
     * Add Help CSH Button
     *
     * @return void
     */
    protected function addHelpButton()
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

    /**
     * Add Shortcut Button
     *
     * @return void
     */
    protected function addShortcutButton()
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

    /**
     * Add "Close" button to DocHeader
     *
     * @return void
     */
    protected function addCloseButton()
    {
        $buttonBar = $this->view
            ->getModuleTemplate()
            ->getDocHeaderComponent()
            ->getButtonBar();

        $uri = $this->uriBuilder
            ->reset()
            ->uriFor('show');

        $closeButton = $buttonBar
            ->makeLinkButton()
            ->setHref($uri)
            ->setIcon($this->iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL))
            ->setTitle('Close');

        $buttonBar->addButton($closeButton);
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
            ->setOnClick($this->uriBuilder->reset()->uriFor('show'))
            ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL))
            ->setTitle('Solr')
            ->setShowLabelText(true);
        $splitButtonBar->addItem($newButton, true);

        $newButton = $buttonBar
            ->makeInputButton()
            ->setName('module')
            ->setValue('whatever')
            ->setOnClick($this->uriBuilder->reset()->uriFor('show'))
            ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL))
            ->setTitle('What ever')
            ->setShowLabelText(true);
        $splitButtonBar->addItem($newButton, false);

        $buttonBar->addButton($splitButtonBar);
    }

    /**
     * Get TYPO3s Database Connection
     *
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Returns the view
     *
     * @return ViewInterface $view
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Sets the view
     *
     * @param ViewInterface $view
     *
     * @return DocHeader
     */
    public function setView($view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * Returns the request
     *
     * @return Request $request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Sets the request
     *
     * @param Request $request
     *
     * @return DocHeader
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }
}
