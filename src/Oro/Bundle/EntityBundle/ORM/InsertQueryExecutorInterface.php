<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;

/**
 * Describes interface for bulk insert query executors
 */
interface InsertQueryExecutorInterface
{
    /**
     * @param string       $className
     * @param array        $fields
     * @param QueryBuilder $selectQueryBuilder
     *
     * @return int
     *
     * @throws QueryException
     * @throws DBALException
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder);
}
