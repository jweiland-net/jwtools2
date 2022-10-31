<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Controller\Ajax;

use ApacheSolrForTypo3\Solr\Domain\Index\IndexService;
use ApacheSolrForTypo3\Solr\Domain\Site\Site;
use ApacheSolrForTypo3\Solr\IndexQueue\Queue;
use JWeiland\Jwtools2\Domain\Repository\SolrRepository;
use JWeiland\Jwtools2\Service\SolrService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class AjaxSolrController
 */
class AjaxSolrController
{
    public function clearIndexAction(ServerRequest $request): ResponseInterface
    {
        $response = new Response();
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

        if ($rootPageUid !== 0 && $configurationNames !== [] && $clear !== []) {
            /** @var ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
            /** @var SolrService $solrService */
            $solrService = $objectManager->get(SolrService::class);

            $site = $this->getSolrSiteFromRequest($request);

            if ($site instanceof Site) {
                foreach ($configurationNames as $configurationName) {
                    $solrService->clearIndexByType($site, $clear, $configurationName);
                }
                $response->getBody()->write(
                    json_encode([
                        'success' => 1,
                    ])
                );
            }
        }

        return $response;
    }

    /**
     * Create index queue entries for given site.
     */
    public function createIndexQueueAction(ServerRequest $request): ResponseInterface
    {
        $response = new Response();
        $site = $this->getSolrSiteFromRequest($request);
        if ($site instanceof Site) {
            /** @var Queue $indexQueue */
            $indexQueue = GeneralUtility::makeInstance(Queue::class);
            $indexingConfigurationsToReIndex = $site->getSolrConfiguration()->getEnabledIndexQueueConfigurationNames();
            foreach ($indexingConfigurationsToReIndex as $indexingConfigurationName) {
                $indexQueue->getInitializationService()->initializeBySiteAndIndexConfiguration(
                    $site,
                    $indexingConfigurationName
                );
            }

            $response->getBody()->write(
                json_encode([
                    'success' => 1,
                ])
            );
        } else {
            $response->getBody()->write(
                json_encode([
                    'success' => 0,
                ])
            );
        }

        return $response;
    }

    public function getProgressAction(ServerRequest $request): ResponseInterface
    {
        $response = new Response();
        $site = $this->getSolrSiteFromRequest($request);

        if ($site instanceof Site) {
            $indexService = GeneralUtility::makeInstance(IndexService::class, $site);

            $response->getBody()->write(
                json_encode([
                    'success' => 1,
                    'progress' => $indexService->getProgress(),
                ])
            );
        } else {
            $response->getBody()->write(
                json_encode([
                    'success' => 0,
                ])
            );
        }

        return $response;
    }

    protected function getRootPageUidFromRequest(ServerRequest $request): int
    {
        $postData = $request->getParsedBody();
        $moduleData = $postData['tx_jwtools2'];
        $rootPageUid = 0;
        if (array_key_exists('rootPageUid', $moduleData)) {
            $rootPageUid = (int)$moduleData['rootPageUid'];
        }

        return $rootPageUid;
    }

    protected function getSolrSiteFromRequest(ServerRequest $request): ?Site
    {
        $rootPageUid = $this->getRootPageUidFromRequest($request);
        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var SolrRepository $solrRepository */
        $solrRepository = $objectManager->get(SolrRepository::class);

        return $solrRepository->findByRootPage($rootPageUid);
    }
}
