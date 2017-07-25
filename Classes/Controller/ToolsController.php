<?php
namespace JWeiland\Jwtools2\Controller;

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

use JWeiland\Jwtools2\Backend\DocHeader;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * Class ToolsController
 *
 * @package JWeiland\Jwtools2\Controller
 */
class ToolsController extends ActionController
{
    /**
     * The default view object to use if none of the resolved views can render
     * a response for the current request.
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Initializes the view before invoking an action method.
     *
     * Override this method to solve assign variables common for all actions
     * or prepare the view in another way before the action is called.
     *
     * @param ViewInterface $view The view to be initialized
     *
     * @return void
     * @api
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var DocHeader $docHeader */
        $docHeader = $this->objectManager->get(DocHeader::class);
        $docHeader
            ->setView($view)
            ->setRequest($this->request)
            ->renderDocHeader();
    }

    /**
     * Show action
     *
     * @return void
     */
    public function showAction()
    {

    }
}
