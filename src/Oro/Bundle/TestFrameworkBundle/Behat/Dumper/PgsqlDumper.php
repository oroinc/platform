<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

class PgsqlDumper extends AbstractDbDumper
{
    /**
     * {@inheritdoc}
     */
    public function dumpDb()
    {
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" pg_dump -h %s -U %s %s > %s/%4$s.sql',
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
    public function restoreDb()
    {
        $this->runProcess(sprintf(
            'PGPASSWORD="%4$s" psql -c "drop database %s;" -h %s -U %s'.
            ' && psql -c "create database %2$s;" -h %3$s -U %4$s '.
            ' && psql -h %3$s -U %4$s %2$s < %5$s/%2$s.sql',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser,
            $this->cacheDir
        ));
    }
}
