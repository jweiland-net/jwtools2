<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A command to convert plain passwords to a salted hash.
 *
 * Be careful, this command can not differ between a plain password and a md5 value!
 * This Command updates every password, which does NOT start with '$'
 */
class CacheQueryCommand extends Command
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Query the cache of TYPO3 regardless of their compression')
            ->addArgument(
                'cacheIdentifier',
                InputArgument::REQUIRED,
                'Set cacheIdentifier. It is just "core", "hash", "pagesection" or any other cache identifier'
            )
            ->addOption(
                'tag',
                't',
                InputOption::VALUE_OPTIONAL,
                'Set tag to get all entryIdentifiers for cache table'
            )
            ->addOption(
                'entryIdentifier',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Set exact entryIdentifier to show'
            )
            ->setHelp(
                'Cache Query'
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return int|null null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $cache = $this->getCache($input->getArgument('cacheIdentifier'));
        if ($cache === null) {
            $output->writeln('Cache with given identifier was not found');
            return 101;
        }

        if ($input->getOption('tag')) {
            $backend = $cache->getBackend();
            if ($backend instanceof TaggableBackendInterface) {
                $entryIdentifiers = $backend->findIdentifiersByTag($input->getOption('tag'));
                $table = new Table($output);
                $table->setHeaders(['Entry Identifiers']);
                array_map(static function (string $entryIdentifier) use ($table): void {
                    $table->setRow($entryIdentifier, [$entryIdentifier]);
                }, $entryIdentifiers);
                $table->render();
            } else {
                $output->writeln('Chosen Backend is not configured as taggable');
                return 102;
            }
        }

        if ($input->getOption('entryIdentifier')) {
            if ($cache->has($input->getOption('entryIdentifier'))) {
                $content = $cache->get($input->getOption('entryIdentifier'));
                if (is_int($content)) {
                    $output->writeln((string)$content);
                } elseif (is_bool($content)) {
                    $output->writeln($content ? 'TRUE' : 'FALSE');
                } elseif (is_array($content)) {
                    $output->writeln(json_encode($content, JSON_PRETTY_PRINT));
                } else {
                    $output->writeln($content);
                }
            } else {
                $output->writeln('No cache with given entryIdentifier found');
                return 103;
            }
        }

        return 0;
    }

    protected function getCache(string $cacheIdentifier): ?FrontendInterface
    {
        try {
            $cache = $this->getCacheManager()->getCache($cacheIdentifier);
        } catch (NoSuchCacheException $noSuchCacheException) {
            return null;
        }

        return $cache;
    }

    protected function getCacheManager(): CacheManager
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }
}