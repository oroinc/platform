<?php

namespace Oro\Bundle\EntityBundle\Manager\Db;

/**
 * Defines the contract for database trigger drivers.
 *
 * Implementations of this interface provide database-specific methods to enable and disable
 * triggers for entity tables. Each driver handles the specific SQL syntax required by
 * its database platform (MySQL, PostgreSQL, etc.).
 */
interface EntityTriggerDriverInterface
{
    /**
     * This method disables all triggers for the particular entity table
     *
     * @return bool
     */
    public function disable();

    /**
     * This method enables back all triggers for the particular entity table
     *
     * @return bool
     */
    public function enable();

    /**
     * Ensuring that proper entityClass name is passed
     *
     * @param string $entityClass
     * @return $this
     */
    public function setEntityClass($entityClass);
}
