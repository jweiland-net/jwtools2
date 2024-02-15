<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\MaintenanceController;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A command which shows a status report about the TYPO3 system
 */
class StatusReportCommand extends Command
{
    private const RETURN_YES = '<info>YES</info>';
    private const RETURN_NO = '<error>NO</error>';
    private SchedulerTaskRepository $taskRepository;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var SymfonyStyle
     */
    protected $ioStyled;

    public function __construct(SchedulerTaskRepository $taskRepository)
    {
        parent::__construct();
        $this->taskRepository = $taskRepository;
    }

    public function configure(): void
    {
        $this
            ->setDescription('Show Status Report')
            ->addOption(
                'exclude-robots-txt-url-check',
                'r',
                InputOption::VALUE_NONE,
                'By default we try to request [domain]/robots.txt if we can not find any in site configuration. Add this option to prevent that check to speed up the report check.'
            )
            ->setHelp(
                'This command checks various settings in your TYPO3 environment and shows them as a report.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->ioStyled = new SymfonyStyle($input, $output);

        $this->checkSiteConfiguration();
        $this->checkScheduler();
        $this->checkDatabaseStatus();

        return 0; // everything fine
    }

    protected function checkSiteConfiguration(): void
    {
        $this->ioStyled->title('Start analyzing Site Configuration');

        foreach ($this->getAllSites() as $site) {
            $this->ioStyled->definitionList(
                'Checking site configuration of domain: ' . $site->getBase(),
                ['robots.txt exists?' => $this->checkRobotsTxt($site)],
                ['robots.txt contains Sitemap?' => $this->checkSitemapOfRobotsTxt($site)],
                ['sitemap.xml exists' => $this->checkSitemapXml($site)],
                ['Error 404 configured' => $this->check404ErrorHandling($site)]
            );
        }
    }

    protected function checkScheduler(): void
    {
        $this->ioStyled->title('Checking scheduler tasks');

        $lastExecution = 0;
        $recurringTasks = [];
        $yesterday = time() - (60 * 60 * 24);

        $tasks = $this->getSchedulerTasks();
        $lastExecution = $this->processTasks($tasks, $recurringTasks, $yesterday, $lastExecution);

        $this->ioStyled->definitionList(
            $lastExecution < $yesterday ? '<error>The last execution was over 24 hours ago</error>' : '<info>Last execution within last 24 hours</info>',
            ...$recurringTasks
        );
    }

    protected function checkDatabaseStatus(): void
    {
        $this->ioStyled->title('Start analyzing DB status');

        $maintenanceController = GeneralUtility::makeInstance(MaintenanceController::class);
        $serverRequest = GeneralUtility::makeInstance(ServerRequest::class);
        $response = $maintenanceController->databaseAnalyzerAnalyzeAction($serverRequest);
        $response->getBody()->rewind();
        $json = $response->getBody()->getContents();

        $result = json_decode($json, true);

        $this->ioStyled->definitionList(
            'Checking database maintenance',
            ['Status' => $result['success'] ? '<info>OK</info>' : '<error>Error</error>'],
            ['Has suggestions?' => !empty($result['suggestions']) ? '<error>YES</error>' : '<info>NO</info>']
        );
    }

    protected function getSchedulerTasks(): array
    {
        return $this->taskRepository->getGroupedTasks();
    }

    protected function checkRobotsTxt(Site $site): string
    {
        return $this->getContentOfRobotsTxt($site) ? self::RETURN_YES : self::RETURN_NO;
    }

    protected function checkSitemapOfRobotsTxt(Site $site): string
    {
        $content = $this->getContentOfRobotsTxt($site);

        return (stripos($content, 'sitemap') !== false) ? self::RETURN_YES : self::RETURN_NO;
    }

    protected function checkSitemapXml(Site $site): string
    {
        return $this->getPageTypeOfSuffix('sitemap.xml', $site) ? self::RETURN_YES : self::RETURN_NO;
    }

    protected function check404ErrorHandling(Site $site): string
    {
        return $this->getErrorHandlingFromSite(404, $site) ? self::RETURN_YES : self::RETURN_NO;
    }

    protected function getContentOfRobotsTxt(Site $site): string
    {
        $content = '';
        $route = $this->getRouteFromSite('robots.txt', $site);
        if (
            $route !== []
            && array_key_exists('type', $route)
            && $route['type'] === 'staticText'
            && array_key_exists('content', $route)
        ) {
            $content = trim((string)$route['content']);
        }

        if ($content === '' && !$this->input->hasOption('exclude-robots-txt-url-check')) {
            $base = rtrim((string)$site->getBase(), '/') . '/';
            $content = @file_get_contents($base . 'robots.txt');

            if (empty($content)) {
                $content = '';
            }
        }

        return $content;
    }

    protected function getRouteFromSite(string $routePath, Site $site): array
    {
        $configuration = $site->getConfiguration();
        if (
            array_key_exists('routes', $configuration)
            && is_array($configuration['routes'])
        ) {
            foreach ($configuration['routes'] as $route) {
                if (
                    is_array($route)
                    && array_key_exists('route', $route)
                    && $route['route'] === $routePath
                ) {
                    return $route;
                }
            }
        }

        return [];
    }

    protected function getPageTypeOfSuffix(string $namedPageType, Site $site): int
    {
        $routeEnhancer = $this->getRouteEnhancerFromSite('PageTypeSuffix', $site);
        if (
            array_key_exists('map', $routeEnhancer)
            && is_array($routeEnhancer['map'])
            && array_key_exists($namedPageType, $routeEnhancer['map'])
        ) {
            return (int)$routeEnhancer['map'][$namedPageType];
        }

        return 0;
    }

    protected function getRouteEnhancerFromSite(string $routeEnhancerName, Site $site): array
    {
        $configuration = $site->getConfiguration();
        if (
            array_key_exists('routeEnhancers', $configuration)
            && array_key_exists($routeEnhancerName, $configuration['routeEnhancers'])
            && is_array($configuration['routeEnhancers'][$routeEnhancerName])
        ) {
            return $configuration['routeEnhancers'][$routeEnhancerName];
        }

        return [];
    }

    protected function getErrorHandlingFromSite(int $errorCode, Site $site): array
    {
        $configuration = $site->getConfiguration();
        if (
            array_key_exists('errorHandling', $configuration)
            && is_array($configuration['errorHandling'])
        ) {
            foreach ($configuration['errorHandling'] as $errorHandling) {
                if (
                    array_key_exists('errorCode', $errorHandling)
                    && $errorHandling['errorCode'] === $errorCode
                ) {
                    return $errorHandling;
                }
            }
        }

        return [];
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }

    /**
     * @return Site[]
     */
    protected function getAllSites(): array
    {
        return GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
    }

    protected function processTasks(array $tasks, array &$recurringTasks, int $yesterday, int $lastExecution): int
    {
        foreach ($tasks as $groups => $taskGroups) {
            foreach ($taskGroups as $taskGroup) {
                if (is_array($taskGroup['tasks']) && count($taskGroup['tasks'])) {
                    foreach ($taskGroup['tasks'] as $task) {
                        if ($task['lastExecutionTime'] > $lastExecution) {
                            $lastExecution = $task['lastExecutionTime'];
                        }

                        $taskTitle = sprintf(
                            '%d: %s - Context: %s',
                            $task['uid'],
                            $task['classTitle'],
                            $task['lastExecutionContext']
                        );

                        $recurringTasks[] = [$taskTitle => $this->getTaskStatus($task, $yesterday)];
                    }
                }
            }
        }

        return $lastExecution;
    }

    protected function getTaskStatus(array $task, int $yesterday): string
    {
        if (isset($task['serializedExecutions']) && $task['serializedExecutions'] !== '') {
            return '<info>running...</info>';
        } elseif ($task['lastExecutionTime'] === 0) {
            return '<error>never executed</error>';
        } else {
            return $task['lastExecutionTime'] < $yesterday ? '<error>scheduled > 24h</error>' : '<info>scheduled < 24h</info>';
        }
    }
}
