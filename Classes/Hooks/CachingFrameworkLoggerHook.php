<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Hooks;

use JWeiland\Jwtools2\Traits\RequestArgumentsTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook to analyze the data just before it gets stored in Caching Framework
 */
class CachingFrameworkLoggerHook implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    use RequestArgumentsTrait;

    /**
     * @var FrontendInterface
     */
    protected $frontend;

    /**
     * Analyze the data. If it matches create a new log entry
     *
     * @param array $parameters Contains entryIdentifier, variable, tags and lifetime
     */
    public function analyze(array $parameters, VariableFrontend $frontend): void
    {
        $this->frontend = $frontend;

        // Don't spend further time, if these variables are empty
        if (!isset($parameters['variable'], $parameters['entryIdentifier'])) {
            return;
        }

        // Add a warning and stop this hook, if no expression records were created
        $cacheExpressions = $this->getCacheExpressions();
        if ($cacheExpressions === []) {
            $this->logger->warning(
                'You have activated the Caching Framework logger in jwtools2, but you ' .
                'have not configured any expression records (or they are empty). Deactivate this feature or ' .
                'configure some records.'
            );
            return;
        }

        $variable = $parameters['variable'];

        // I know nothing about the datatype, structure or whatever in $variable.
        // IMO a string representation is a good start for analyzing: preg_match, strpos, ...
        if (!is_string($variable)) {
            $variable = json_encode($variable);
        }

        foreach ($cacheExpressions as $cacheExpression) {
            try {
                $this->checkVariableForExpression($variable, $parameters['entryIdentifier'], $cacheExpression);
            } catch (\Exception $exception) {
                $this->logger->error(
                    '[jwtools2] Error occurred while analyzing cache entry: ' . $exception->getMessage()
                );
            }
        }
    }

    protected function checkVariableForExpression(string $variable, string $entryIdentifier, array $cacheExpression): void
    {
        if ($cacheExpression['is_regexp']) {
            $matches = [];
            if (preg_match('/' . preg_quote($cacheExpression['expression'], '/') . '/', $variable, $matches)) {
                $this->createLogEntry($entryIdentifier, $cacheExpression);
            }
        } elseif (mb_strpos($variable, $cacheExpression['expression']) !== false) {
            $this->createLogEntry($entryIdentifier, $cacheExpression);
        }
    }

    protected function createLogEntry(string $entryIdentifier, array $cacheExpression): void
    {
        $context = [
            'entryIdentifier' => $entryIdentifier,
            'cacheIdentifier' => $this->frontend->getIdentifier(),
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
            $context
        );
    }

    protected function getCacheExpressions(): array
    {
        $cacheExpressions = [];
        try {
            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tx_jwtools2_cache_expression');
            $statement = $queryBuilder
                ->select('title', 'is_regexp', 'expression')
                ->where(
                    $queryBuilder->expr()->neq('title', $queryBuilder->createNamedParameter('')),
                    $queryBuilder->expr()->neq('expression', $queryBuilder->createNamedParameter(''))
                )
                ->from('tx_jwtools2_cache_expression')
                ->executeQuery();

            while ($cacheExpression = $statement->fetchAssociative()) {
                $cacheExpressions[] = $cacheExpression;
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                'Error while querying table tx_jwtools2_cache_expression: ' . $exception->getMessage()
            );
        }

        return $cacheExpressions;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
