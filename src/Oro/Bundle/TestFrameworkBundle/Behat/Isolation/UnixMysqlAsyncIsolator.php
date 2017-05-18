<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;

final class UnixMysqlAsyncIsolator extends AbstractDbOsRelatedIsolator implements IsolatorInterface
{
    const TIMEOUT = '240';

    protected $connection;

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return
            $this->isApplicableOS()
            && DatabaseDriverInterface::DRIVER_MYSQL === $container->getParameter('database_driver');
    }

    /** {@inheritdoc} */
    public function getName()
    {
        return "MySql DB";
    }

    /** {@inheritdoc} */
    protected function makeDump()
    {
        $this->runProcess(sprintf(
            'MYSQL_PWD=%s mysqldump -h %s -u %s %s > %s',
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
            'MYSQL_PWD=%s mysql -h %s -u %s %s < %s',
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

    protected function createTempDb()
    {
        if ($this->isDbExists($this->dbTempName)) {
            $this->dropTempDb();
        }

        $this->runProcess(sprintf(
            'MYSQL_PWD=%s mysql -e "create database %s;" -h %s -u %s',
            $this->dbPass,
            $this->dbTempName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    protected function createDb()
    {
        if ($this->isDbExists($this->dbName)) {
            $this->dropDb();
        }

        $this->runProcess(sprintf(
            'MYSQL_PWD=%s mysql -e "create database %s;" -h %s -u %s',
            $this->dbPass,
            $this->dbName,
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
            'do mysql -h %1$s -u %2$s -p%3$s -s -N -e "use %4$s;rename table %4$s.$table to %5$s.$table;"; done;',
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

    protected function dropDb()
    {
        $this->runProcess(sprintf(
            'MYSQL_PWD=%s mysql -e "drop database %s;" -h %s -u %s',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    protected function dropTempDb()
    {
        $this->runProcess(sprintf(
            'MYSQL_PWD=%s mysql -e "drop database %s;" -h %s -u %s',
            $this->dbPass,
            $this->dbTempName,
            $this->dbHost,
            $this->dbUser
        ));
    }

    /**
     * @param string $dbName
     * @return bool
     */
    private function isDbExists($dbName)
    {
        $process = $this->runProcess(sprintf(
            'mysql -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA '.
            'WHERE SCHEMA_NAME = \'%s\'" -h %s -u %s -p%s',
            $dbName,
            $this->dbHost,
            $this->dbUser,
            $this->dbPass
        ));

        return false !== strpos($process->getOutput(), $dbName);
    }
}
