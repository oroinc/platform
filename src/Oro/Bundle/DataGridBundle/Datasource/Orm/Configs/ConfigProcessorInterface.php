<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm\Configs;

use Doctrine\ORM\QueryBuilder;

/**
 * Defines the contract for ORM query configuration processors.
 *
 * Configuration processors convert datagrid configuration arrays into Doctrine ORM {@see QueryBuilder}
 * instances. They handle both the main data query and optional count query, allowing for
 * optimized pagination and total record counting.
 */
interface ConfigProcessorInterface
{
    /**
     * Creates query builder for main query from configs array
     *
     * @param array $config
     *
     * @return QueryBuilder
     */
    public function processQuery(array $config);

    /**
     * Optionally creates query builder for count query from configs array
     *
     * @param array $config
     *
     * @return QueryBuilder|null
     */
    public function processCountQuery(array $config);
}
