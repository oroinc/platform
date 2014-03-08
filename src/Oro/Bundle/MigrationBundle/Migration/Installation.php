<?php

namespace Oro\Bundle\MigrationBundle\Migration;

/**
 * An interface for installation scripts
 */
abstract class Installation extends Migration
{
    /**
     * Gets a number of the last migration version implemented by this installation script
     *
     * @return string
     */
    abstract public function getMigrationVersion();
}
