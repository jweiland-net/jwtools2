<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Traits;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait to get Merged Request Arguments. Used for replacing the old _GP array.
 */
trait RequestArgumentsTrait
{
    public function getGPValue(string $key): ?string
    {
        $request = $this->getServerRequest();
        return $request->getParsedBody()[$key] ?? $request->getQueryParams()[$key];
    }

    public function getGetArguments(): array
    {
        return $this->getServerRequest()->getQueryParams();
    }

    public function getPostArguments(): array
    {
        return $this->getServerRequest()->getParsedBody() ?? [];
    }

    public function getMergedPostAndGetValues(): array
    {
        $request = $this->getServerRequest();

        return array_merge($request->getQueryParams(), $request->getParsedBody());
    }

    private function getServerRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? ServerRequestFactory::fromGlobals();
    }
}
