<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

interface DbDumperInterface
{
    /**
     * Dump database to storage for restore
     *
     * @return void
     */
    public function dumpDb();

    /**
     * Restore database from dump
     *
     * @return void
     */
    public function restoreDb();
}
