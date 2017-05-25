<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

abstract class AbstractDbOsRelatedIsolator extends AbstractOsRelatedIsolator implements IsolatorInterface
{
    /** @var string */
    protected $dbHost;

    /** @var  string */
    protected $dbPort;

    /** @var string */
    protected $dbName;

    /** @var string */
    protected $dbTempName;

    /** @var string */
    protected $dbPass;

    /** @var string */
    protected $dbUser;

    /**
     * @var string full path to DB dump file
     */
    protected $dbDump;

    /** @var  Process */
    protected $restoreDbFromDumpProcess;

    /**
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $kernel->boot();
        $container = $kernel->getContainer();

        $this->dbHost = $container->getParameter('database_host');
        $this->dbPort = $container->getParameter('database_port');
        $this->dbName = $container->getParameter('database_name');
        $this->dbUser = $container->getParameter('database_user');
        $this->dbPass = $container->getParameter('database_password');
        $this->dbDump = sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->dbName.TokenGenerator::generateToken('db');
        $this->dbTempName = $this->dbName.'_temp'.TokenGenerator::generateToken('db');
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Create temp database</info>');
        $this->createTempDb();
        $event->writeln('<info>Dumping current application database</info>');
        $this->makeDump();
        $event->writeln('<info>Start process for restore Temp Db from dump</info>');
        $this->startRestoreTempDbFromDump();
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $this->waitForProcess();
        $this->dropDb();
        $this->createDb();
        $this->renameTempDb();
        $this->startRestoreTempDbFromDump();
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
        $event->writeln('<info>Stop restoring db from dump</info>');
        $this->restoreDbFromDumpProcess->stop();
        $event->writeln('<info>Remove Temp Db</info>');
        $this->dropTempDb();
        $event->writeln('<info>Remove Db dump</info>');
        unlink($this->dbDump);
    }

    /** {@inheritdoc} */
    public function restoreState(RestoreStateEvent $event)
    {
        if (false === is_file($this->dbDump)) {
            throw new RuntimeException('Can\'t restore Db without sql dump');
        }
        $event->writeln('<info>Begin to restore the state of Db...</info>');

        $event->writeln('<info>Drop Db</info>');
        $this->dropDb();
        $this->dropTempDb();
        $event->writeln('<info>Restore Db from dump</info>');
        $this->createDb();
        $this->restoreDbFromDump();

        $event->writeln('<info>Remove Db dump</info>');

        unlink($this->dbDump);
        $event->writeln('<info>Db was restored from dump</info>');
    }

    /** {@inheritdoc} */
    public function isOutdatedState()
    {
        return is_file($this->dbDump);
    }

    /** {@inheritdoc} */
    public function getTag()
    {
        return 'database';
    }

    /**
     * @param string $commandline The command line to run
     * @param int $timeout The timeout in seconds
     * @return Process
     */
    protected function runProcess($commandline, $timeout = 120)
    {
        $process = new Process($commandline);

        $process->setTimeout($timeout);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process;
    }

    protected function waitForProcess()
    {
        while ($this->restoreDbFromDumpProcess->isRunning()) {
            // waiting for process to finish or fail by timeout
        }

        if (!$this->restoreDbFromDumpProcess->isSuccessful()) {
            throw new ProcessFailedException($this->restoreDbFromDumpProcess);
        }
    }

    abstract protected function makeDump();

    abstract protected function createDb();

    abstract protected function createTempDb();

    abstract protected function dropDb();

    abstract protected function dropTempDb();

    /**
     * Rename temp database to current application database
     */
    abstract protected function renameTempDb();

    abstract protected function restoreDbFromDump();

    abstract protected function startRestoreTempDbFromDump();
}
