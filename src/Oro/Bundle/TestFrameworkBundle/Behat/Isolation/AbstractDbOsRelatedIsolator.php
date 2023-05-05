<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Doctrine\DBAL\DriverManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Common logic for database isolators
 */
abstract class AbstractDbOsRelatedIsolator extends AbstractOsRelatedIsolator implements IsolatorInterface
{
    protected string $dbHost = '';
    protected string $dbPort = '';
    protected string $dbName = '';
    protected string $dbPass = '';
    protected string $dbUser = '';

    public function __construct(KernelInterface $kernel)
    {
        $kernel->boot();

        $container = $kernel->getContainer();
        $this->setupDatabaseUrl($container->getParameter('database_dsn'));
    }

    protected function setupDatabaseUrl(string $databaseUrl): void
    {
        $params = DriverManager::getConnection([
            'url' => $databaseUrl,
        ])->getParams();

        $this->dbHost = $params['host'];
        $this->dbPort = (string) ($params['port'] ?? '');
        $this->dbName = $params['dbname'];
        $this->dbUser = (string) ($params['user'] ?? '');
        $this->dbPass = (string) ($params['password'] ?? '');
    }

    abstract protected function dump(): void;

    abstract protected function restore(): void;

    abstract protected function getDropDbCommand(): string;

    /** {@inheritdoc} */
    public function getTag()
    {
        return 'database';
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Dumping current application database</info>');
        $this->dump();
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $event->writeln('<info>Restore database from dump</info>');
        $this->dropDb($this->dbName);
        $this->restore();
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
        $event->writeln('<info>Begin to restore the state of Db...</info>');
        $event->writeln('<info>Drop Db</info>');
        $this->dropDb($this->dbName);

        $event->writeln('<info>Restore Db from dump</info>');
        $this->restore();
        $event->writeln('<info>Db was restored from dump</info>');
    }

    protected function dropDb(string $dbName): void
    {
        $this->runProcess(
            strtr(
                $this->getDropDbCommand(),
                [
                    '%database_host%' => $this->dbHost,
                    '%database_port%' => $this->dbPort,
                    '%database_name%' => $dbName,
                    '%database_user%' => $this->dbUser,
                    '%database_password%' => $this->dbPass,
                ]
            )
        );
    }

    /**
     * @param string $commandline The command line to run
     * @param int    $timeout The timeout in seconds
     * @return Process
     */
    protected function runProcess($commandline, $timeout = 120)
    {
        $process = Process::fromShellCommandline($commandline);

        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }
}
