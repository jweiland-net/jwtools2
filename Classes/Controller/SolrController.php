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

use JWeiland\Jwtools2\Service\SolrService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Class SolrController
 *
 * @package JWeiland\Jwtools2\Controller
 */
class SolrController extends ActionController
{
    /**
     * Solr overview
     *
     * @return void
     */
    public function showAction()
    {
    }

    /**
     * Creates index queue for all sites
     *
     * @return void
     */
    public function createIndexQueueForAllSitesAction()
    {
        $solrService = GeneralUtility::makeInstance(SolrService::class);
        $result = $solrService->createIndexQueueForSites();

        $this->view->assign('errors', $result['errors']);
        $this->view->assign('totalItemsAddedToIndexQueue', $result['totalItemsAddedToIndexQueue']);
    }
}
