<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

use Symfony\Component\Process\Process;

class MysqlDumper extends AbstractDbDumper
{
    /**
     * {@inheritdoc}
     */
    public function dumpDb()
    {
        $dumpDbProcess = new Process(sprintf(
            'MYSQL_PWD=%s mysqldump -h %s -u %s %s > %s/%4$s.sql',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->cacheDir
        ));

        $dumpDbProcess->setTimeout(30);
        $dumpDbProcess->run();
    }

    /**
     * {@inheritdoc}
     */
    public function restoreDb()
    {
        $restoreDbProcess = new Process(sprintf(
            'MYSQL_PWD=%s mysql -e "drop database %s;" -h %s -u %s'.
            ' && mysql -e "create database %2$s;" -h %3$s -u %4$s'.
            ' && mysql -h %3$s -u %4$s %2$s < %5$s/%2$s.sql',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser,
            $this->cacheDir
        ));

        $restoreDbProcess->setTimeout(30);
        $restoreDbProcess->run();
    }
}
