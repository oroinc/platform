<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterFinishTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\AfterIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeIsolatedTestEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\BeforeStartTestsEvent;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\Event\RestoreStateEvent;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;

class WindowsMysqlIsolator extends AbstractDbOsRelatedIsolator implements IsolatorInterface
{
    const TIMEOUT = 120;

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return
            self::isApplicableOS()
            && 'pdo_mysql' === $container->getParameter('database_driver');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'MySql Db';
    }

    /** {@inheritdoc} */
    protected function getApplicableOs()
    {
        return [
            AbstractOsRelatedIsolator::WINDOWS_OS,
        ];
    }

    protected function restoreDbFromDump()
    {
        if (false === is_file($this->dbDump)) {
            throw new RuntimeException('You can restore DB state without dump');
        }

        $this->runProcess(sprintf(
            'SET MYSQL_PWD=%s&mysql -e "drop database %s;" -h %s -u %s'.
            ' && SET MYSQL_PWD=%1$s& mysql -e "create database %2$s;" -h %3$s -u %4$s'.
            ' && SET MYSQL_PWD=%1$s& mysql -h %3$s -u %4$s %2$s < %5$s',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser,
            $this->dbDump
        ));
    }

    protected function makeDump()
    {
        $this->runProcess(sprintf(
            'SET MYSQL_PWD=%s& mysqldump -h %s -u %s %s > %s',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->dbDump
        ));
    }

    protected function createDb()
    {
        $this->runProcess(sprintf(
            'SET MYSQL_PWD=%s & mysql -e "create database %s;" -h %s -u %s',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    protected function createTempDb()
    {
        $this->runProcess(sprintf(
            'SET MYSQL_PWD=%s & mysql -e "create database %s;" -h %s -u %s',
            $this->dbPass,
            $this->dbTempName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    protected function dropDb()
    {
        $this->runProcess(sprintf(
            'SET MYSQL_PWD=%s&mysql -e "drop database %s;" -h %s -u %s',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    protected function dropTempDb()
    {
        $this->runProcess(sprintf(
            'SET MYSQL_PWD=%s&mysql -e "drop database %s;" -h %s -u %s',
            $this->dbPass,
            $this->dbTempName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    /**
     * Rename temp database to current application database
     */
    protected function renameTempDb()
    {
        $this->restoreDbFromDumpProcess = $this->runProcess(sprintf(
            'for table in `mysql -h %s -u %s -p%s -s -N -e "use %s;show tables from %4$s;"`; '.
            'do mysql -u root -p06021980 -s -N -e "use %4$s;rename table %4$s.$table to %5$s.$table;"; done;',
            $this->dbHost,
            $this->dbUser,
            $this->dbPass,
            $this->dbTempName,
            $this->dbName
        ));
    }

    protected function startRestoreTempDbFromDump()
    {
        $this->restoreDbFromDumpProcess = new Process(sprintf(
            'exec mysql -h %s -u %s -p%s %s < %s',
            $this->dbHost,
            $this->dbUser,
            $this->dbPass,
            $this->dbTempName,
            $this->dbDump
        ));

        $this->restoreDbFromDumpProcess
            ->setTimeout(self::TIMEOUT)
            ->start();
    }
}
