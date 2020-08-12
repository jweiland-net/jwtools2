<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\RealUrl;

use DmitryDulepov\Realurl\Decoder\UrlDecoder;
use DmitryDulepov\Realurl\EncodeDecoderBase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * If there is a change in a record which results into a new URI, realurl still keeps the old
 * URI and can resolve the correct page. But, if you clear the realurl cache the old information is gone
 * and page can not be resolved. In that state realurl tries to do a decoding. If you make use of lookUpTable
 * configuration with an individual alias_field like CONCAT(title, '-', uid) this process will break decoding and
 * you will get the useless page part (f.e. my-first-birthday-2) in GETVar which will than break extbase
 * processing which expects an UID in showAction.
 * Use this class as postVarSets userFunc in your realurl configuration. If you have UID in front or back of your
 * URI part there is a chance to resolve URI correctly.
 */
class ConvertTableAliasToId
{
    /**
     * Convert origValue to UID value
     *
     * @param array $parameters
     * @param EncodeDecoderBase $parentObject
     * @return int|null
     */
    public function convert(array $parameters, EncodeDecoderBase $parentObject)
    {
        if ($parentObject instanceof UrlDecoder && $this->checkParameters($parameters)) {
            $id = $this->getIdFromOrigValue($parameters);
            if (!empty($id)) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Get ID as INT from origValue
     *
     * @param array $parameters
     * @return int
     */
    protected function getIdFromOrigValue(array $parameters): int
    {
        $id = 0;

        $position = $this->getPositionOfIdField(
            $fields = $this->getFieldsFromCleanedAlias(
                $parameters['setup']['lookUpTable']['alias_field']
            ),
            $parameters['setup']['lookUpTable']['id_field']
        );

        $matches = [];
        if (preg_match('/^(?P<first>\d+)?.*?(?P<last>\d+)?$/', $parameters['origValue'], $matches)) {
            if ($position === 'first' && isset($matches['first'])) {
                $id = (int)$matches['first'];
            } elseif ($position === 'last' && isset($matches['last'])) {
                $id = (int)$matches['last'];
            }
        }

        return $id;
    }

    /**
     * Get position as string where id_field is in alias_field
     *
     * @param array $fields
     * @param string $idField
     * @return string
     */
    protected function getPositionOfIdField(array $fields, string $idField): string
    {
        if (reset($fields) === $idField) {
            $position = 'first';
        } elseif (end($fields) === $idField) {
            $position = 'last';
        } else {
            // currently not supported
            $position = 'mid';
        }

        return $position;
    }

    /**
     * Check, if given parameters are valid for further use in this class
     *
     * @param array $parameters
     * @return bool
     */
    protected function checkParameters(array $parameters): bool
    {
        // if origValue is INT, there won't be a problem while decode
        if (MathUtility::canBeInterpretedAsInteger($parameters['origValue'])) {
            return false;
        }

        if (!$this->lookUpTableConfigurationExists($parameters)) {
            return false;
        }

        $lookUpTableConfiguration = $this->getLookUpTableConfiguration($parameters);
        if (!$this->isValidLookUpTableConfiguration($lookUpTableConfiguration)) {
            return false;
        }

        if (!$this->checkIfIdFieldExistsInAliasField(
            $lookUpTableConfiguration['id_field'],
            $lookUpTableConfiguration['alias_field']
        )) {
            return false;
        }

        return true;
    }

    /**
     * Check, if ID fields exists in alias field
     *
     * @param string $idField
     * @param string $aliasField
     * @return bool
     */
    protected function checkIfIdFieldExistsInAliasField(string $idField, string $aliasField): bool
    {
        if ($idField === $aliasField) {
            return true;
        }

        // fast check
        if (strpos($aliasField, $idField) !== false) {
            // detailed check
            $cleanFields = $this->getFieldsFromCleanedAlias($aliasField);
            return in_array($idField, $cleanFields, true);
        }
        return false;
    }

    /**
     * Remove all DB functions from string
     *
     * @param string $aliasField
     * @return array
     */
    protected function getFieldsFromCleanedAlias(string $aliasField): array
    {
        $cleanedAlias = preg_replace('/[\w-_]+\(/', '', $aliasField);
        $cleanedAlias = str_replace(')', '', $cleanedAlias);
        return GeneralUtility::trimExplode(',', $cleanedAlias);
    }

    /**
     * Check, if lookUpTable configuration is valid
     *
     * @param array $configuration
     * @return bool
     */
    protected function isValidLookUpTableConfiguration(array $configuration): bool
    {
        return isset($configuration['table'])
            && isset($configuration['id_field'])
            && isset($configuration['alias_field']);
    }

    /**
     * Check, if lookUpTable configuration part exists in parameters
     *
     * @param array $parameters
     * @return bool
     */
    protected function lookUpTableConfigurationExists(array $parameters): bool
    {
        return array_key_exists('lookUpTable', $parameters['setup'])
            && is_array($parameters['setup']['lookUpTable']);
    }

    /**
     * Get configuration of lookUpTable
     *
     * @param array $parameters
     * @return array
     */
    protected function getLookUpTableConfiguration(array $parameters): array
    {
        if ($this->lookUpTableConfigurationExists($parameters)) {
            return $parameters['setup']['lookUpTable'];
        }
        return [];
    }
}
