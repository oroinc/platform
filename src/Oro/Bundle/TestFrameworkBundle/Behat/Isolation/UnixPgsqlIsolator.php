<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;

final class UnixPgsqlIsolator extends AbstractDbOsRelatedIsolator implements IsolatorInterface
{
    const TIMEOUT = 120;

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

    /** {@inheritdoc} */
    protected function restoreDbFromDump()
    {
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" psql -h %s -U %s %s < %s',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->dbDump
        ));
    }

    /** {@inheritdoc} */
    protected function getApplicableOs()
    {
        return [
            AbstractOsRelatedIsolator::LINUX_OS,
            AbstractOsRelatedIsolator::MAC_OS,
        ];
    }

    protected function createDb()
    {
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" psql -c "create database %s;" -h %s -U %s ',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    protected function createTempDb()
    {
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" psql -c "create database %s;" -h %s -U %s ',
            $this->dbPass,
            $this->dbTempName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    protected function dropDb()
    {
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" psql -c "drop database %s;" -h %s -U %s',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    protected function dropTempDb()
    {
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" psql -c "drop database %s;" -h %s -U %s',
            $this->dbPass,
            $this->dbTempName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    /** {@inheritdoc} */
    protected function renameTempDb()
    {
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" psql -c "ALTER DATABASE %s RENAME TO %s;" -h %s -U %s',
            $this->dbPass,
            $this->dbTempName,
            $this->dbName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    protected function startRestoreTempDbFromDump()
    {
        $this->restoreDbFromDumpProcess = new Process(sprintf(
            'PGPASSWORD="%s" exec psql -h %s -U %s %s < %s',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbTempName,
            $this->dbDump
        ));

        $this->restoreDbFromDumpProcess
            ->setTimeout(self::TIMEOUT)
            ->start();
    }
}
