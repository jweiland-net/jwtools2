<?php
namespace JWeiland\Jwtools2\Controller;

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

use ApacheSolrForTypo3\Solr\System\Configuration\ConfigurationPageResolver;
use ApacheSolrForTypo3\Solr\System\Configuration\ExtensionConfiguration;
use ApacheSolrForTypo3\Solr\Util;
use JWeiland\Jwtools2\Backend\DocHeader;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Class ToolsController
 *
 * @package JWeiland\Jwtools2\Controller
 */
class ToolsController extends AbstractController
{
    /**
     * Overview action
     *
     * @return void
     */
    public function overviewAction()
    {
    }
}
