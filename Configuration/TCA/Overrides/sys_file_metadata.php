<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3')) {
    die('Access denied.');
}

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Mark the columns configured in "typo3RequiredColumnsForFiles" as required in the metadata form of images.
// This mirrors FileMetaDataValidationService, which also validates images only.
// Columns of sys_file (e.g. "name") are not part of this TCA and are skipped.
// As TCA is cached, the cache has to be flushed after changing the extension setting.
try {
    $requiredColumns = GeneralUtility::trimExplode(
        ',',
        (string)GeneralUtility::makeInstance(ExtensionConfiguration::class)
            ->get('jwtools2', 'typo3RequiredColumnsForFiles'),
        true,
    );
} catch (ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException) {
    $requiredColumns = [];
}

$imageType = (string)FileType::IMAGE->value;
if (isset($GLOBALS['TCA']['sys_file_metadata']['types'][$imageType])) {
    foreach ($requiredColumns as $requiredColumn) {
        if (!isset($GLOBALS['TCA']['sys_file_metadata']['columns'][$requiredColumn])) {
            continue;
        }

        $GLOBALS['TCA']['sys_file_metadata']['types'][$imageType]['columnsOverrides'][$requiredColumn]['config']['required'] = true;
    }
}
