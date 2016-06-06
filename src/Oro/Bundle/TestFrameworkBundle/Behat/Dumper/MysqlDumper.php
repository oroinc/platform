<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

class MysqlDumper extends AbstractDbDumper
{
    /**
     * {@inheritdoc}
     */
    public function dump()
    {
        $this->runProcess(sprintf(
            'MYSQL_PWD=%s mysqldump -h %s -u %s %s > %s/%4$s.sql',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->cacheDir
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function restore()
    {
        $this->runProcess(sprintf(
            'MYSQL_PWD=%s mysql -e "drop database %s;" -h %s -u %s'.
            ' && MYSQL_PWD=%1$s mysql -e "create database %2$s;" -h %3$s -u %4$s'.
            ' && MYSQL_PWD=%1$s mysql -h %3$s -u %4$s %2$s < %5$s/%2$s.sql',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser,
            $this->cacheDir
        ));
    }
}
