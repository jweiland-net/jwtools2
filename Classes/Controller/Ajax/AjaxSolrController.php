<?php
namespace JWeiland\Jwtools2\Controller\Ajax;

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

use ApacheSolrForTypo3\Solr\Domain\Index\IndexService;
use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\IndexQueue\Queue;
use JWeiland\Jwtools2\Domain\Repository\SolrRepository;
use JWeiland\Jwtools2\Service\SolrService;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AjaxSolrController
 */
class AjaxSolrController
{
    /**
     * @param ServerRequest $request
     * @param Response $response
     * @return Response
     */
    public function clearIndexAction(ServerRequest $request, Response $response)
    {
        $postData = $request->getParsedBody();
        $moduleData = $postData['tx_jwtools2'];

        $rootPageUid = $this->getRootPageUidFromRequest($request);
        $configurationNames = [];
        if (array_key_exists('configurationNames', $moduleData)) {
            $configurationNames = (array)$moduleData['configurationNames'];
        }
        $clear = [];
        if (array_key_exists('clear', $moduleData)) {
            $clear = (array)$moduleData['clear'];
        }

        if (!empty($rootPageUid) && !empty($configurationNames) && !empty($clear)) {
            /** @var ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var SolrService $solrService */
            $solrService = $objectManager->get(SolrService::class);

            $site = $this->getSolrSiteFromRequest($request);

            if ($site instanceof Site) {
                foreach ($configurationNames as $configurationName) {
                    $solrService->clearIndexByType($site, $clear, $configurationName);
                }
                $response->getBody()->write(json_encode([
                    'success' => 1
                ]));
            }
        }

        return $response;
    }

    /**
     * Create index queue entries for given site.
     *
     * @param ServerRequest $request
     * @param Response $response
     * @return Response
     */
    public function createIndexQueueAction(ServerRequest $request, Response $response)
    {
        $site = $this->getSolrSiteFromRequest($request);

        /** @var Queue $indexQueue */
        $indexQueue = GeneralUtility::makeInstance(Queue::class);
        $indexingConfigurationsToReIndex = $site->getSolrConfiguration()->getEnabledIndexQueueConfigurationNames();
        foreach ($indexingConfigurationsToReIndex as $indexingConfigurationName) {
            $indexQueue->getInitializationService()->initializeBySiteAndIndexConfiguration(
                $site,
                $indexingConfigurationName
            );
        }

        $response->getBody()->write(json_encode([
            'success' => 1
        ]));

        return $response;
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     * @return Response
     */
    public function getProgressAction(ServerRequest $request, Response $response)
    {
        $site = $this->getSolrSiteFromRequest($request);
        if ($site instanceof Site) {
            /** @var IndexService $indexService */
            $indexService = GeneralUtility::makeInstance(
                IndexService::class,
                $site
            );
            $indexService->setContextTask(null);

            $response->getBody()->write(json_encode([
                'success' => 1,
                'progress' => $indexService->getProgress()
            ]));
        }

        return $response;
    }

    /**
     * @param ServerRequest $request
     * @return int
     */
    protected function getRootPageUidFromRequest(ServerRequest $request)
    {
        $postData = $request->getParsedBody();
        $moduleData = $postData['tx_jwtools2'];
        $rootPageUid = 0;
        if (array_key_exists('rootPageUid', $moduleData)) {
            $rootPageUid = (int)$moduleData['rootPageUid'];
        }
        return $rootPageUid;
    }

    /**
     * @param ServerRequest $request
     * @return Site|null
     */
    protected function getSolrSiteFromRequest(ServerRequest $request)
    {
        $rootPageUid = $this->getRootPageUidFromRequest($request);
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var SolrRepository $solrRepository */
        $solrRepository = $objectManager->get(SolrRepository::class);
        return $solrRepository->findByRootPage($rootPageUid);
    }
}
