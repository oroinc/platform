<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Psr\Log\LoggerInterface;

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
