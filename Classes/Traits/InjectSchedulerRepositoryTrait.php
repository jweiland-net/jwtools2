<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Traits;

use JWeiland\Jwtools2\Domain\Repository\SchedulerRepository;

/**
 * Trait to inject SchedulerRepository. Mostly used in controllers.
 */
trait InjectSchedulerRepositoryTrait
{
    protected SchedulerRepository $schedulerRepository;

    public function injectSchedulerRepository(SchedulerRepository $schedulerRepository): void
    {
        $this->schedulerRepository = $schedulerRepository;
    }
}
