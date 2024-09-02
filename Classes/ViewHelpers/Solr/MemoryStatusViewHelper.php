<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\ViewHelpers\Solr;

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Class MemoryStatusViewHelper
 */
class MemoryStatusViewHelper extends AbstractViewHelper
{
    /**
     * @var Registry
     */
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Show percentage memory usage
     */
    public function render(): float
    {
        $memoryPeakUsage = $this->registry->get('jwtools2-solr', 'memoryPeakUsage', 0);
        $bytes = $this->getBytesFromMemoryLimit();
        if ($bytes <= 0) {
            return 0;
        }

        return round(100 / $bytes * $memoryPeakUsage, 2);
    }

    protected function getBytesFromMemoryLimit(): int
    {
        $iniLimit = (string)@ini_get('memory_limit');
        return $iniLimit === '-1' ? -1 : GeneralUtility::getBytesFromSizeMeasurement($iniLimit);
    }
}
