<?php
declare(strict_types = 1);
namespace JWeiland\Jwtools2\ViewHelpers\Solr;

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

use TYPO3\CMS\Core\Registry;
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

    /**
     * inject registry
     *
     * @param Registry $registry
     */
    public function injectRegistry(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Show index status
     *
     * @return float
     */
    public function render(): float
    {
        $memoryPeakUsage = $this->registry->get('jwtools2-solr', 'memoryPeakUsage', 0);
        $memoryLimit = $this->returnBytes(ini_get('memory_limit'));

        return round(100 / $memoryLimit * $memoryPeakUsage, 2);
    }

    /**
     * Convert values like 256M to bytes
     *
     * @param string $bytes
     * @return int
     */
    protected function returnBytes(string $bytes): int
    {
        $bytes = trim($bytes);
        $last = strtolower($bytes[strlen($bytes) - 1]);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            case 'g':
                $bytes *= 1024;
                // no break
            case 'm':
                $bytes *= 1024;
                // no break
            case 'k':
                $bytes *= 1024;
        }

        return $bytes;
    }
}
