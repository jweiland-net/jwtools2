<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Traits;

use TYPO3\CMS\Core\Imaging\IconFactory;

/**
 * Trait to inject IconFactory
 */
trait InjectIconFactoryTrait
{
    protected IconFactory $iconFactory;

    public function injectIconFactory(IconFactory $iconFactory): void
    {
        $this->iconFactory = $iconFactory;
    }
}
