<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/jwtools2.
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Jwtools2\Command;

use Doctrine\DBAL\Driver\Statement;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A command to convert plain passwords to a salted hash.
 * Be careful, this command can not differ between a plain password and a md5 value!
 * This Command updates every password, which does NOT start with '$'
 */
class ConvertPlainPasswordToHashCommand extends Command
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var PasswordHashInterface[]
     */
    protected $hashingMethods = [];

    /**
     * @var PasswordHashFactory
     */
    protected $passwordHashFactory;

    /**
     * @var array
     */
    protected $modeMapping = [
        'FE' => [
            'table' => 'fe_users',
        ],
        'BE' => [
            'table' => 'be_users',
        ],
    ];

    public function configure(): void
    {
        $this
            ->setDescription('Convert plain passwords to Salted Hashes')
            ->addArgument(
                'mode',
                InputArgument::OPTIONAL,
                'Set to FE or BE to update hashes only for a special TYPO3 Mode'
            )
            ->setHelp(
                'This command can not differ between a plain password and a md5 value. It updates all password in DB which does not start with $'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        $this->passwordHashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);

        if (strtoupper($input->getArgument('mode') ?? '') === 'FE') {
            $this->updateUsers('FE');
        } elseif (strtoupper($input->getArgument('mode') ?? '') === 'BE') {
            $this->updateUsers('BE');
        } else {
            $this->updateUsers('FE');
            $this->updateUsers('BE');
        }

        return 0;
    }

    protected function updateUsers(string $mode): void
    {
        $counter = 0;
        $connection = $this->getConnectionPool()->getConnectionForTable($this->modeMapping[$mode]['table']);
        $statement = $this->getStatementForUsers($this->modeMapping[$mode]['table']);
        while ($user = $statement->fetch()) {
            if (empty($user['password'])) {
                continue;
            }
            try {
                $this->passwordHashFactory->get($user['password'], $mode);
                if ($this->output->getVerbosity() === 32) {
                    $this->output->write('X');
                }
                $this->output->writeln(
                    'Password for User ' . $user['uid'] . ' was not updated. Password already hashed.',
                    OutputInterface::VERBOSITY_VERBOSE
                );
            } catch (InvalidPasswordHashException $e) {
                // Perfect. No HashInstance can process this user password. Start update
                $connection->update(
                    $this->modeMapping[$mode]['table'],
                    [
                        'password' => $this->getNewHashedPassword($user['password'], $mode),
                    ],
                    [
                        'uid' => (int)$user['uid'],
                    ]
                );
                if ($this->output->getVerbosity() === 32) {
                    $this->output->write('.', false, OutputInterface::VERBOSITY_QUIET);
                }
                $counter++;
            }
        }

        $this->output->write('', true);
        $this->output->writeln(
            sprintf(
                'We have updated %d users of table: %s',
                $counter,
                $this->modeMapping[$mode]['table']
            )
        );
    }

    protected function getNewHashedPassword(string $password, string $mode): string
    {
        $time = microtime(true);
        $newPassword = $this->getDefaultHashInstance($mode)->getHashedPassword($password);
        $this->output->writeln(
            sprintf(
                'Generating a new password costs: %f seconds.',
                microtime(true) - $time
            ),
            OutputInterface::VERBOSITY_VERBOSE
        );
        $this->output->writeln(
            '--> Hashed password will be stored (Hash shortened): ' . substr($newPassword, 0, 10),
            OutputInterface::VERBOSITY_DEBUG
        );
        return $newPassword;
    }

    protected function getDefaultHashInstance(string $mode): PasswordHashInterface
    {
        if (
            !array_key_exists('hashInstance', $this->modeMapping[$mode])
            || !$this->modeMapping[$mode]['hashInstance'] instanceof PasswordHashInterface
        ) {
            $this->modeMapping[$mode]['hashInstance'] = $this->passwordHashFactory->getDefaultHashInstance($mode);
        }
        return $this->modeMapping[$mode]['hashInstance'];
    }

    protected function getStatementForUsers(string $tableName): Statement
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        return $queryBuilder
            ->select('uid', 'password')
            ->from($tableName)
            ->execute();
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
