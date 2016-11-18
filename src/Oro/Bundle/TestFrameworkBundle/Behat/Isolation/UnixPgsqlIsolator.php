<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;

final class UnixPgsqlIsolator extends AbstractOsRelatedIsolator implements IsolatorInterface
{
    const TIMEOUT = 120;

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
        $this->dbDump = sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->dbName;
    }

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return
            self::isApplicableOS()
            && 'pdo_pgsql' === $container->getParameter('database_driver');
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return 'PostgreSql Db';
    }

    /** {@inheritdoc} */
    public function start(BeforeStartTestsEvent $event)
    {
        $event->writeln('<info>Dumping current application database</info>');
        $this->makeDump();
    }

    /** {@inheritdoc} */
    public function beforeTest(BeforeIsolatedTestEvent $event)
    {
    }

    /** {@inheritdoc} */
    public function afterTest(AfterIsolatedTestEvent $event)
    {
        $this->dropDb();
        $this->restoreDbFromDump();
    }

    /** {@inheritdoc} */
    public function terminate(AfterFinishTestsEvent $event)
    {
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
        $event->writeln('<info>Restore Db from dump</info>');
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
    protected function getApplicableOs()
    {
        return [
            AbstractOsRelatedIsolator::LINUX_OS,
            AbstractOsRelatedIsolator::MAC_OS,
        ];
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

    protected function dropDb()
    {
        $process = sprintf(
            'PGPASSWORD="%s" psql -h %s -U %s %s -t -c "'.
            'select \'drop table \"\' || tablename || \'\" cascade;\' from pg_tables where schemaname=\'public\'"'.
            '| psql -h %s -U %s %s',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->dbHost,
            $this->dbUser,
            $this->dbName
        );
        $this->runProcess($process);
    }

    /** {@inheritdoc} */
    protected function restoreDbFromDump()
    {
        var_dump('restore db from dump');
        $process = sprintf(
            'PGPASSWORD="%s" psql -h %s -U %s %s < %s',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->dbDump
        );
        $this->runProcess($process);
    }

    /** {@inheritdoc} */
    protected function makeDump()
    {
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" pg_dump -h %s -U %s %s > %s',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->dbDump
        ));
    }
}
