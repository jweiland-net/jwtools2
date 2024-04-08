<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;

/**
 * ToolsController for backend module
 */
class ToolsController extends AbstractController
{
    public function initializeAction(): void
    {
        parent::initializeAction();
    }

    public function overviewAction(): ResponseInterface
    {
        return $this->moduleTemplate->renderResponse('Overview');
    }
}
