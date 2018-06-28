<?php
namespace JWeiland\Jwtools2\Utility;

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

use JWeiland\Jwtools2\Configuration\Settings;

/**
 * Class SQLStatementUtility
 */
class SQLStatementUtility
{
    /**
     * Creates array with CREATE TABLE entries with boosting field
     *
     * @param array $tables
     * @return array
     */
    public static function prepareCreateTableQueryWithBoostingField(array $tables)
    {
        $result = [];

        foreach ($tables as $table) {
            if ($table) {
                $result[] = 'CREATE TABLE ' . $table
                    . ' ( '
                    . Settings::SOLR_BOOSTING_KEYWORDS_FIELD_NAME
                    . ' varchar(255) DEFAULT \'\' NOT NULL );';
            }
        }

        return $result;
    }
}
