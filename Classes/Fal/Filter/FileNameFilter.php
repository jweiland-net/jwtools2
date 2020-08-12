<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
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
     * whether to also show the hidden files (don't show them by default)
     *
     * @var bool
     */
    protected static $showHiddenFilesAndFolders = false;

    /**
     * Filter method that checks if a file/folder name starts with a dot (e.g. .htaccess)
     *
     * We have to use -1 as the „don't include“ return value, as call_user_func() will return FALSE
     * If calling the method succeeded and thus we can't use that as a return value.
     *
     * @param string $itemName
     * @param string $itemIdentifier
     * @param string $parentIdentifier
     * @param array $additionalInformation Additional information (driver dependent) about the inspected item
     * @param DriverInterface $driverInstance
     * @return bool|int -1 if the file should not be included in a listing
     */
    public static function filterHiddenFilesAndFolders(
        $itemName,
        $itemIdentifier,
        $parentIdentifier,
        array $additionalInformation,
        DriverInterface $driverInstance
    ) {
        // Only apply the filter if you want to hide the hidden files
        if (
            self::$showHiddenFilesAndFolders === false
            && strpos($itemIdentifier, '/.') !== false
            && strpos($itemIdentifier, '/.youtube') === false
            && strpos($itemIdentifier, '/.vimeo') === false
        ) {
            return -1;
        }
        return true;
    }

    /**
     * Gets the info whether the hidden files are also displayed currently
     *
     * @static
     * @return bool
     */
    public static function getShowHiddenFilesAndFolders(): bool
    {
        return self::$showHiddenFilesAndFolders;
    }

    /**
     * set the flag to show (or hide) the hidden files
     *
     * @static
     * @param bool $showHiddenFilesAndFolders
     * @return bool
     */
    public static function setShowHiddenFilesAndFolders($showHiddenFilesAndFolders): bool
    {
        return self::$showHiddenFilesAndFolders = (bool)$showHiddenFilesAndFolders;
    }
}
