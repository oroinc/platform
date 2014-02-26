<?php

namespace Oro\Bundle\InstallerBundle\Migrations;

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
     */
    public function execute();
}
