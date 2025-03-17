<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Hooks;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Hook to move translated tt_content records to new col_pos, too
 */
class MoveTranslatedContentElementsHook
{
    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        // For "move"-cmd both cmdmap and datamap has to be filled
        if (
            isset($dataHandler->cmdmap['tt_content'], $dataHandler->datamap['tt_content'])
            && !empty($dataHandler->cmdmap['tt_content'])
            && !empty($dataHandler->datamap['tt_content'])
        ) {
            foreach ($dataHandler->cmdmap['tt_content'] as $uid => $cmdRecordInDefaultLanguage) {
                // sys_language_uid
                $languageField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];

                // Check for "move"-cmd and, if datamap contains instructions for same UID
                if (
                    array_key_exists('move', $cmdRecordInDefaultLanguage)
                    && array_key_exists($uid, $dataHandler->datamap['tt_content'])
                    && ($dataRecordInDefaultLanguage = $dataHandler->datamap['tt_content'][$uid])
                    && array_key_exists('colPos', $dataRecordInDefaultLanguage)
                    && MathUtility::canBeInterpretedAsInteger($uid)
                ) {
                    foreach ($this->getOverlayRecords((int)$uid, $dataHandler) as $translatedContentRecord) {
                        // Moved tt_content records will add "colPos" and "sys_language_uid" to datamap.
                        // Both values were set as string in core.
                        // We do that for translated records here, too.
                        $dataHandler->datamap['tt_content'][$translatedContentRecord['uid']] = [
                            'colPos' => (string)$dataRecordInDefaultLanguage['colPos'],
                            $languageField => (string)$translatedContentRecord[$languageField],
                        ];
                    }
                }
            }
        }
    }

    /**
     * @param string $command Something like "move" or "copy"
     * @param string $table The table name
     * @param string|int $uid Currently string (GET-request), but maybe in future INT
     * @param array $value The values to update. Should be array (but not always)
     * @param array|false $pasteUpdate false if uninitialized, else array of current loop
     * @param array $pasteDatamap array collecting all $pasteUpdate's. Used as datamap for sub TCE
     */
    public function processCmdmap_postProcess(
        string $command,
        string $table,
        $uid,
        $value,
        DataHandler $dataHandler,
        $pasteUpdate,
        &$pasteDatamap,
    ): void {
        if (
            $command === 'move'
            && $table === 'tt_content'
            && MathUtility::canBeInterpretedAsInteger($uid)
        ) {
            // sys_language_uid
            $languageField = $GLOBALS['TCA']['tt_content']['ctrl']['languageField'];
            foreach ($this->getOverlayRecords((int)$uid, $dataHandler) as $translatedContentRecord) {
                // Moved tt_content records will add "colPos" and "sys_language_uid" to datamap.
                // Both values were set as string in core.
                // We do that for translated records here, too.
                $pasteDatamap['tt_content'][$translatedContentRecord['uid']] = [
                    'colPos' => (string)$pasteUpdate['colPos'],
                    $languageField => (string)$translatedContentRecord[$languageField],
                ];
            }
        }
    }

    protected function getOverlayRecords($uid, DataHandler $dataHandler): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $dataHandler->BE_USER->workspace));

        $statement = $queryBuilder->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['tt_content']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT, ':pointer'),
                ),
            )
            ->executeQuery();

        $contentRecords = [];
        while ($contentRecord = $statement->fetchAssociative()) {
            $contentRecords[] = $contentRecord;
        }

        return $contentRecords;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
