<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Isolation;

use Symfony\Component\DependencyInjection\ContainerInterface;

class WindowsMysqlIsolator extends OsRelatedIsolator implements IsolatorInterface
{
    use AbstractDbIsolator;

    /** {@inheritdoc} */
    public function start()
    {
        $this->runProcess(sprintf(
            'SET MYSQL_PWD=%s& mysqldump -h %s -u %s %s > %s/%4$s.sql',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->cacheDir
        ));
    }

    /** {@inheritdoc} */
    public function beforeTest()
    {}

    /** {@inheritdoc} */
    public function afterTest()
    {
        $this->runProcess(sprintf(
            'SET MYSQL_PWD=%s&mysql -e "drop database %s;" -h %s -u %s'.
            ' && SET MYSQL_PWD=%1$s& mysql -e "create database %2$s;" -h %3$s -u %4$s'.
            ' && SET MYSQL_PWD=%1$s& mysql -h %3$s -u %4$s %2$s < %5$s/%2$s.sql',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser,
            $this->cacheDir
        ));
    }

    /** {@inheritdoc} */
    public function terminate()
    {}

    /** {@inheritdoc} */
    public function isApplicable(ContainerInterface $container)
    {
        return
            self::isApplicableOS()
            && 'pdo_mysql' === $container->getParameter('database_driver');
    }

    /** {@inheritdoc} */
    protected function getApplicableOs()
    {
        return [
            OsRelatedIsolator::WINDOWS_OS,
        ];
    }
}
