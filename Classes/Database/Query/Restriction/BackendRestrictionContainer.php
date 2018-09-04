<?php
declare(strict_types = 1);
namespace JWeiland\Jwtools2\Database\Query\Restriction;

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
    protected $defaultRestrictionTypes = [
        DeletedRestriction::class
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
