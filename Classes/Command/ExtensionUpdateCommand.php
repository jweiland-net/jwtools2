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
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Utility\UpdateScriptUtility;

/**
 * A command to execute extension updates realized with class.ext_update.php
 */
class ExtensionUpdateCommand extends Command
{
    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure(): void
    {
        $this
            ->setDescription('Check and execute all extension updates (class.ext_update.php)')
            ->addArgument(
                'ext_key',
                InputArgument::OPTIONAL,
                'If set, only specified extension will be updated'
            )
            ->addOption(
                'list',
                'l',
                InputOption::VALUE_NONE,
                'If set, we only show the update possible active extension keys'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'If you execute all update scripts and a script will throw an exception this command will normally break and stop. Add this option to ignore that exception and process all other extensions'
            )
            ->setHelp(
                'This command loops through all active extensions and searches for available class.ext_update.php files. If method access() returns true, this command will call main() method.'
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return int|null null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $packages = $this->getUpdatePossibleActivePackages();

        if ($input->getOption('list')) {
            $output->writeln('List of extensions where update is possible');
            $table = new Table($output);
            $table->setHeaders(['ext_key']);
            $table->addRows($packages);
            $table->render();

            return 0;
        }
        if ($input->getArgument('ext_key')) {
            if (!array_key_exists($input->getArgument('ext_key'), $packages)) {
                $output->writeln('Given ext_key does not exist in possible extension list');
                return 1;
            }
            $output->writeln('Start update script of extension: ' . $input->getArgument('ext_key'));
            if (!$this->updateExtension($input->getArgument('ext_key'))) {
                $output->writeln(sprintf(
                    'Error while executing main() method of extension: %s',
                    $input->getArgument('ext_key')
                ));
                return 2;
            }
        } else {
            foreach ($packages as $extKey => $_) {
                $output->writeln('Start update script of extension: ' . $extKey);
                if (!$this->updateExtension($extKey)) {
                    $output->writeln(sprintf(
                        'Error while executing main() method of extension: %s',
                        $input->getArgument('ext_key')
                    ));
                    if ($input->getOption('force')) {
                        continue;
                    }
                    return 2;
                }
            }
        }

        $output->writeln('Update successful');

        return 0; // everything fine
    }

    /**
     * Execute update script of extension
     *
     * @param string $extKey
     * @return bool
     */
    protected function updateExtension(string $extKey): bool
    {
        $updateScriptUtility = GeneralUtility::makeInstance(UpdateScriptUtility::class);
        try {
            $updateScriptUtility->executeUpdateIfNeeded($extKey);
        } catch (\Exception $exception) {
            return false;
        }
        return true;
    }

    /**
     * Get all active extensions with class.ext_update.php
     * where an update is possible (Method call <access> returns true)
     *
     * @return array
     */
    protected function getUpdatePossibleActivePackages(): array
    {
        $packageManager = GeneralUtility::makeInstance(
            PackageManager::class,
            GeneralUtility::makeInstance(DependencyOrderingService::class)
        );
        $activePackages = $packageManager->getActivePackages();
        $updateScriptUtility = GeneralUtility::makeInstance(UpdateScriptUtility::class);

        $packages = [];
        foreach ($activePackages as $activePackage) {
            if ($updateScriptUtility->checkUpdateScriptExists($activePackage->getPackageKey())) {
                $packages[$activePackage->getPackageKey()] = [
                    $activePackage->getPackageKey()
                ];
            }
        }

        ksort($packages);

        return $packages;
    }
}
