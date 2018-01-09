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

    /**
     * AbstractDocHeader constructor.
     *
     * @param Request $request
     * @param ViewInterface $view
     */
    public function __construct(Request $request, ViewInterface $view)
    {
        $this->request = $request;
        $this->view = $view;
    }

    /**
     * inject uriBuilder
     *
     * @param UriBuilder $uriBuilder
     *
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
     *
     * @return void
     */
    public function injectIconFactory(IconFactory $iconFactory)
    {
        $this->iconFactory = $iconFactory;
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
            ->uriFor('overview', [], 'Tools');

        $closeButton = $buttonBar
            ->makeLinkButton()
            ->setHref($uri)
            ->setIcon($this->iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL))
            ->setTitle('Close');

        $buttonBar->addButton($closeButton);
    }

    /**
     * Get Link to create new configuration records of defined type
     *
     * @param string $url
     *
     * @return string
     */
    protected function getLinkForUrl($url)
    {
        return 'window.location.href=' . GeneralUtility::quoteJSvalue($url) . '; return false;';
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
}
