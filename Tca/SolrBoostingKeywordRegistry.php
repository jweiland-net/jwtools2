<?php
declare(strict_types = 1);
namespace JWeiland\Jwtools2\Tca;

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

use JWeiland\Jwtools2\Configuration\ExtConf;
use JWeiland\Jwtools2\Utility\SQLStatementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SolrBoostingKeywordRegistry
 */
class SolrBoostingKeywordRegistry
{
    /**
     * Ext conf
     *
     * @var ExtConf
     */
    protected $extConf;

    /**
     * SolrBoostingKeywordRegistry constructor.
     */
    public function __construct()
    {
        $this->extConf = GeneralUtility::makeInstance(ExtConf::class);
    }

    /**
     * Adds "jwtools2_boosting_keywords" to affected tables
     * This method is called as tablesDefinitionIsBeingBuilt slot
     *
     * @param $sqlString array
     * @return array
     */
    public function addBoostingKeywordFieldToAffectedTables(array $sqlString): array
    {
        $sqlString = $this->generateSQLStringForAffectedTables($sqlString);

        return ['sqlString' => $sqlString];
    }

    /**
     * Generates SQL String for adding columns to affected tables
     *
     * @param $sqlString array
     * @return array
     */
    private function generateSQLStringForAffectedTables(array $sqlString): array
    {
        $createTableSqlString = SQLStatementUtility::prepareCreateTableQueryWithBoostingField(
            $this->extConf->getTablesToAddKeywordBoosting()
        );

        array_push($sqlString, ...$createTableSqlString);

        return $sqlString;
    }
}
