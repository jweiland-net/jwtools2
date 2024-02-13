<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Traits;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait to get Merged Request Arguments. Used for replacing the old _GP array.
 */
trait RequestArgumentsTrait
{
    public function getGPValue(string $key): ?string
    {
        /** @var ServerRequestInterface $request */
        $request = GeneralUtility::makeInstance(ServerRequestInterface::class);

        return $request->getParsedBody()[$key] ?? $request->getQueryParams()[$key];
    }

    public function getMergedPostAndGetValues(): array
    {
        /** @var ServerRequestInterface $request */
        $request = GeneralUtility::makeInstance(ServerRequestInterface::class);

        return array_merge($request->getQueryParams(), $request->getParsedBody());
    }
}
