<?php
namespace JWeiland\Jwtools2\Configuration;

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
use TYPO3\CMS\Core\SingletonInterface;

/**
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class ExtConf implements SingletonInterface
{
    /**
     * Enable transfer of TypoScript property current
     *
     * @var bool
     */
    protected $typo3TransferTypoScriptCurrent = false;

    /**
     * Enable Solr features
     *
     * @var bool
     */
    protected $solrEnable = false;

    /**
     * Solr Scheduler Task
     *
     * @var int
     */
    protected $solrSchedulerTaskUid = 0;

    /**
     * Tables to add keyword boosting
     *
     * @var array
     */
    protected $tablesToAddKeywordBoosting = [];

    /**
     * constructor of this class
     * This method reads the global configuration and calls the setter methods.
     */
    public function __construct()
    {
        // get global configuration
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['jwtools2']);
        if (is_array($extConf) && count($extConf)) {
            // call setter method foreach configuration entry
            foreach ($extConf as $key => $value) {
                $methodName = 'set' . ucfirst($key);
                if (method_exists($this, $methodName)) {
                    $this->$methodName($value);
                }
            }
        }
    }

    /**
     * Returns the typo3TransferTypoScriptCurrent
     *
     * @return bool $typo3TransferTypoScriptCurrent
     */
    public function getTypo3TransferTypoScriptCurrent()
    {
        return $this->typo3TransferTypoScriptCurrent;
    }

    /**
     * Sets the typo3TransferTypoScriptCurrent
     *
     * @param bool $typo3TransferTypoScriptCurrent
     *
     * @return void
     */
    public function setTypo3TransferTypoScriptCurrent($typo3TransferTypoScriptCurrent)
    {
        $this->typo3TransferTypoScriptCurrent = (bool)$typo3TransferTypoScriptCurrent;
    }

    /**
     * Returns the solrEnable
     *
     * @return bool $solrEnable
     */
    public function getSolrEnable()
    {
        return $this->solrEnable;
    }

    /**
     * Sets the solrEnable
     *
     * @param bool $solrEnable
     *
     * @return void
     */
    public function setSolrEnable($solrEnable)
    {
        $this->solrEnable = (bool)$solrEnable;
    }

    /**
     * Returns the solrSchedulerTaskUid
     *
     * @return int $solrSchedulerTaskUid
     */
    public function getSolrSchedulerTaskUid()
    {
        return $this->solrSchedulerTaskUid;
    }

    /**
     * Sets the solrSchedulerTaskUid
     *
     * @param int $solrSchedulerTaskUid
     *
     * @return void
     */
    public function setSolrSchedulerTaskUid($solrSchedulerTaskUid)
    {
        $this->solrSchedulerTaskUid = (int)$solrSchedulerTaskUid;
    }

    /**
     * Gets TablesToAddKeywordBoosting
     *
     * @return array
     */
    public function getTablesToAddKeywordBoosting()
    {
        return $this->tablesToAddKeywordBoosting;
    }

    /**
     * Sets TablesToAddKeywordBoosting
     *
     * @param string $tablesToAddKeywordBoosting
     * @return void
     */
    public function setTablesToAddKeywordBoosting($tablesToAddKeywordBoosting)
    {
        // Remove whitespaces from user input to prevent trailing spaces
        $tablesToAddKeywordBoosting = str_replace(' ', '', $tablesToAddKeywordBoosting);
        $this->tablesToAddKeywordBoosting = explode(',', $tablesToAddKeywordBoosting);
    }
}
