<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

/**
 * Implement this repository interface to create a custom datagrid query builder
 */
interface RepositoryInterface
{
    /**
     * Create datagrid query builder
     *
     * @return mixed Doctrine/ODM/MongoDB/Query/Builder or Doctrine/ORM/QueryBuilder
     */
    public function createDatagridQueryBuilder();
}
