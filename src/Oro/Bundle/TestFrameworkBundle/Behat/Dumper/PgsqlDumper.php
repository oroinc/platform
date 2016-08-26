<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

class PgsqlDumper extends AbstractDbDumper
{
    /**
     * {@inheritdoc}
     */
    public function dump()
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
    public function restore()
    {
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" psql -c "drop database %s;" -h %s -U %s',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser
        ));
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" psql -c "create database %s;" -h %s -U %s ',
            $this->dbPass,
            $this->dbName,
            $this->dbHost,
            $this->dbUser
        ));
        $this->runProcess(sprintf(
            'PGPASSWORD="%s" psql -h %s -U %s %s < %s/%4$s.sql',
            $this->dbPass,
            $this->dbHost,
            $this->dbUser,
            $this->dbName,
            $this->cacheDir
        ));
    }
}
