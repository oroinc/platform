<?php

namespace Oro\Bundle\DataGridBundle\Datasource\Orm\Configs;

use Doctrine\ORM\QueryBuilder;

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
