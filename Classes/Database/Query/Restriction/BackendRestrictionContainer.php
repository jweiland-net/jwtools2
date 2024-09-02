<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Database\Query\Restriction;

use TYPO3\CMS\Core\Database\Query\Restriction\AbstractRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;

/**
 * This is the container with restrictions, that can be used for Backend Queries
 * It only adds a deleted restriction
 */
class BackendRestrictionContainer extends AbstractRestrictionContainer
{
    /**
     * Backend restriction classes.
     *
     * @var QueryRestrictionInterface[]
     */
    protected array $defaultRestrictionTypes = [
        DeletedRestriction::class,
    ];

    /**
     * Creates instances of the registered Backend restriction classes
     */
    public function __construct()
    {
        foreach ($this->defaultRestrictionTypes as $restrictionType) {
            $this->add($this->createRestriction($restrictionType));
        }
    }
}
