<?php

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'ext-jwtools2-be-module-icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:jwtools2/Resources/Public/Icons/module_tools.svg',
    ],
];
