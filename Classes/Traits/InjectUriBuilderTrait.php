<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Traits;

use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Trait to inject UriBuilder
 */
trait InjectUriBuilderTrait
{
    protected ?UriBuilder $uriBuilder = null;

    public function injectUriBuilder(UriBuilder $uriBuilder): void
    {
        $this->uriBuilder = $uriBuilder;
    }
}
