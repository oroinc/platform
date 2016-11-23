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
