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

use DmitryDulepov\Realurl\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RealurlUtility
 * Remove the static cache from RealurlUtility as our solr scheduler task loops over each root page
 * and needs the CURRENT domain name
 */
class RealurlUtility extends Utility
{
    /**
     * @var string
     */
    protected $currentHttpHost = '';

    /**
     * Obtains the current host.
     *
     * @return string
     */
    public function getCurrentHost() {
        $currentHost = (string)GeneralUtility::getIndpEnv('HTTP_HOST');
        if ($this->currentHttpHost !== $currentHost) {
            // Call user hooks
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['getHost'])) {
                foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['realurl']['getHost'] as $userFunc) {
                    $hookParams = array(
                        'host' => $currentHost,
                    );
                    $newHost = GeneralUtility::callUserFunction($userFunc, $hookParams, $this);
                    if (!empty($newHost) && is_string($newHost)) {
                        $currentHost = $newHost;
                    }
                }
            }
            $this->currentHttpHost = $currentHost;
        }

        return $this->currentHttpHost;
    }
}
