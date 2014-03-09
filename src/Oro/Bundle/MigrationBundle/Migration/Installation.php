<?php

namespace Oro\Bundle\MigrationBundle\Migration;

/**
 * An interface for installation scripts
 */
interface Installation extends Migration
{
    /**
     * Gets a number of the last migration version implemented by this installation script
     *
     * @return string
     */
    public function getMigrationVersion();
}
