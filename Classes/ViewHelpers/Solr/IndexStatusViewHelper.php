<?php

namespace JWeiland\Jwtools2\ViewHelpers\Solr;

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
use ApacheSolrForTypo3\Solr\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class IndexStatusViewHelper
 */
class IndexStatusViewHelper extends AbstractViewHelper
{
    /**
     * Initialize all arguments.
     */
    public function initializeArguments()
    {
        $this->registerArgument(
            'site',
            Site::class,
            'The site class to retrieve the status from',
            true
        );
    }

    /**
     * Show index status
     *
     * @return float
     */
    public function render()
    {
        /** @var IndexService $indexService */
        $indexService = GeneralUtility::makeInstance(
            IndexService::class,
            $this->arguments['site']
        );
        $indexService->setContextTask(null);

        return $indexService->getProgress();
    }
}
