<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Hooks\Exception;

/**
 * This exception will be thrown, if a cache expression record with activates exception handling matches an invalid
 * cache entry
 */
class PreventStoringFalseCacheEntryException extends \Exception
{
}
