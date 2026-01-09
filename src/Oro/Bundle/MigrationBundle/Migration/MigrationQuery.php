<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Psr\Log\LoggerInterface;

/**
 * Defines the contract for custom SQL queries executed during migrations.
 *
 * This interface allows migrations to execute custom SQL queries that cannot be expressed
 * through the schema modification API. Implementations provide a description of the query
 * for logging purposes and an execute method to run the query with access to a logger.
 */
interface MigrationQuery
{
    /**
     * Gets a query description
     * If this query has several sub queries you can return an array of descriptions for each sub query
     *
     * @return string|string[]
     */
    public function getDescription();

    /**
     * Executes a query
     *
     * @param LoggerInterface $logger A logger which can be used to log details of an execution process
     */
    public function execute(LoggerInterface $logger);
}
