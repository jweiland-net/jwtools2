<?php
namespace JWeiland\Jwtools2\Slot;

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

use JWeiland\Jwtools2\Utility\SQLStatementUtility;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Controller\ConfigurationController;

/**
 * Class ExtensionConfigurationSlot
 */
class ExtensionConfigurationSlot
{
    /**
     * Update Database
     *
     * @param string $extensionKey
     * @param array $newConfiguration
     * @param ConfigurationController $configurationController
     * @return void
     */
    public function updateDatabase(
        $extensionKey,
        array $newConfiguration,
        ConfigurationController $configurationController
    ) {
        /** @var SchemaMigrator $schemaMigrator */
        $schemaMigrator = GeneralUtility::makeInstance(SchemaMigrator::class);

        $tablesToChange = GeneralUtility::trimExplode(
            ',',
            $newConfiguration['solrTablesToAddKeywordBoosting']['value']
        );

        $sqlStatement = SQLStatementUtility::prepareCreateTableQueryWithBoostingField($tablesToChange);
        $schemaMigrator->install($sqlStatement);
    }
}
