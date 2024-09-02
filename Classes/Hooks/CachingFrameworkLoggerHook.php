<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Hooks;

use JWeiland\Jwtools2\Hooks\Exception\PreventStoringFalseCacheEntryException;
use JWeiland\Jwtools2\Traits\RequestArgumentsTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook to analyze the cache data just before it gets stored in Caching Framework
 */
class CachingFrameworkLoggerHook implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use RequestArgumentsTrait;

    /**
     * Analyze the data. If it matches create a new log entry
     *
     * @param array $parameters Contains entryIdentifier, variable, tags and lifetime
     * @throws PreventStoringFalseCacheEntryException
     */
    public function analyze(array $parameters, VariableFrontend $frontend): void
    {
        // Don't spend further time, if these variables are empty
        if (!isset($parameters['variable'], $parameters['entryIdentifier'])) {
            return;
        }

        // Add a warning and stop this hook, if no expression records were created
        $cacheExpressionRecords = $this->getCacheExpressionRecords();
        if ($cacheExpressionRecords === []) {
            $this->logger->warning(
                'You have activated the Caching Framework logger in jwtools2, but you ' .
                'have not configured any expression records (or they are empty). Deactivate this feature or ' .
                'configure some records.',
            );
            return;
        }

        $variable = $parameters['variable'];

        // I know nothing about the datatype, structure or whatever in $variable.
        // IMO a string representation is a good start for analyzing: preg_match, strpos, ...
        if (!is_string($variable)) {
            $variable = json_encode($variable);
        }

        $matchingExpressionRecords = $this->getExpressionRecordsMatchingVariable($variable, $cacheExpressionRecords);
        foreach ($matchingExpressionRecords as $cacheExpressionRecord) {
            $this->createLogEntry(
                $parameters['entryIdentifier'],
                $frontend->getIdentifier(),
                $cacheExpressionRecord,
            );

            if ($cacheExpressionRecord['is_exception']) {
                // throw exception in FE only or also in BE, if fe_only is disabled
                if (
                    $cacheExpressionRecord['exception_fe_only'] === 0
                    || (
                        $cacheExpressionRecord['exception_fe_only'] === 1
                        && ApplicationType::fromRequest($this->getServerRequest())->isFrontend()
                    )
                ) {
                    throw new PreventStoringFalseCacheEntryException(
                        '[jwtools2] CF logger prevents inserting invalid cache entry',
                        1720607181,
                    );
                }
            }
        }
    }

    protected function getExpressionRecordsMatchingVariable(string $variable, array $cacheExpressionRecords): array
    {
        $matchingExpressionRecords = [];
        foreach ($cacheExpressionRecords as $cacheExpressionRecord) {
            try {
                if ($this->isVariableMatchingExpressionRecord($variable, $cacheExpressionRecord)) {
                    $matchingExpressionRecords[] = $cacheExpressionRecord;
                }
            } catch (\Exception $exception) {
                $this->logger->error(
                    '[jwtools2] Error occurred while analyzing cache entry: ' . $exception->getMessage(),
                );
            }
        }

        return $matchingExpressionRecords;
    }

    protected function isVariableMatchingExpressionRecord(string $variable, array $cacheExpressionRecord): bool
    {
        if ($cacheExpressionRecord['is_regexp']) {
            if (preg_match('/' . preg_quote($cacheExpressionRecord['expression'], '/') . '/', $variable)) {
                return true;
            }
        } elseif (mb_strpos($variable, $cacheExpressionRecord['expression']) !== false) {
            return true;
        }

        return false;
    }

    protected function createLogEntry(string $entryIdentifier, string $cacheIdentifier, array $cacheExpression): void
    {
        $context = [
            'entryIdentifier' => $entryIdentifier,
            'cacheIdentifier' => $cacheIdentifier,
            'cacheExpression' => $cacheExpression,
            'backtrace' => debug_backtrace(2), // 0 => without objects
            'request' => GeneralUtility::getIndpEnv('_ARRAY'),
            'GET' => $this->getGetArguments(),
            'POST' => $this->getPostArguments(),
        ];

        // Yes, we log that as error. In most cases you have problems on LIVE/PRODUCTION where severities of info and
        // warning are not logged.
        $this->logger->error(
            '[jwtools2] Query Cache detection. A cache expression matches.',
            $context,
        );
    }

    protected function getCacheExpressionRecords(): array
    {
        $cacheExpressions = [];
        try {
            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_jwtools2_cache_expression');
            $statement = $queryBuilder
                ->select('title', 'is_regexp', 'is_exception', 'exception_fe_only', 'expression')
                ->where(
                    $queryBuilder->expr()->neq('title', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->neq('expression', $queryBuilder->createNamedParameter('')),
                )
                ->from('tx_jwtools2_cache_expression')
                ->executeQuery();

            while ($cacheExpression = $statement->fetchAssociative()) {
                $cacheExpressions[] = $cacheExpression;
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error while querying table tx_jwtools2_cache_expression: ' . $exception->getMessage(),
            );
        }

        return $cacheExpressions;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
