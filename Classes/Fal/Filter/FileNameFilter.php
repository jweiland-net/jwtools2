<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Fal\Filter;

use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

/**
 * Utility methods for filtering filenames.
 * SF: It prevents showing hidden files/folders except .youtube and .vimeo files
 */
class FileNameFilter
{
    /**
     * Whether to also show the hidden files (don't show them by default)
     *
     * @var bool
     */
    protected static bool $showHiddenFilesAndFolders = false;

    /**
     * Filter method that checks if a file/folder name starts with a dot (e.g. .htaccess)
     * We have to use -1 as the „don't include“ return value, as call_user_func() will return FALSE
     * If calling the method succeeded and thus we can't use that as a return value.
     *
     * @return bool|int -1 if the file should not be included in a listing
     */
    public static function filterHiddenFilesAndFolders(
        string $itemName,
        string $itemIdentifier,
        string $parentIdentifier,
        array $additionalInformation,
        DriverInterface $driverInstance,
    ): bool|int {
        // Only apply the filter if hidden files should not be listed
        if (
            self::$showHiddenFilesAndFolders === false
            && str_contains($itemIdentifier, '/.')
            && !str_contains($itemIdentifier, '/.youtube')
            && !str_contains($itemIdentifier, '/.vimeo')
        ) {
            return -1;
        }
        return true;
    }

    /**
     * Gets the info whether the hidden files are also displayed currently
     */
    public static function getShowHiddenFilesAndFolders(): bool
    {
        return self::$showHiddenFilesAndFolders;
    }

    /**
     * Set the flag to show (or hide) the hidden files
     */
    public static function setShowHiddenFilesAndFolders(bool $showHiddenFilesAndFolders): bool
    {
        return self::$showHiddenFilesAndFolders = $showHiddenFilesAndFolders;
    }
}
