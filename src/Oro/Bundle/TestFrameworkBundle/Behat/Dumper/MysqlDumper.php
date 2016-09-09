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
            'MYSQL_PWD=%s mysql -e "drop database %s;" -h %s -u %s',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser
        ));
        $this->runProcess(sprintf(
            'MYSQL_PWD=%s mysql -e "create database %s;" -h %s -u %s',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser
        ));
        $this->runProcess(sprintf(
            'MYSQL_PWD=%s mysql -h %s -u %s %s < %s/%4$s.sql',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->cacheDir
        ));
    }
}
