<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Dumper;

interface DbDumperInterface
{
    /**
     * Dumps the database.
     */
    public function dumpDb();

    /**
     * Restores the database from dump.
     */
    public function restoreDb();
}
