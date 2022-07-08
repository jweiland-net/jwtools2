<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\ViewHelpers\Solr;

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

    public function injectRegistry(Registry $registry): void
    {
        $this->registry = $registry;
    }

    /**
     * Show index status
     */
    public function render(): float
    {
        $memoryPeakUsage = $this->registry->get('jwtools2-solr', 'memoryPeakUsage', 0);
        $memoryLimit = $this->returnBytes(ini_get('memory_limit'));

        return round(100 / $memoryLimit * $memoryPeakUsage, 2);
    }

    /**
     * Convert values like 256M to bytes
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
