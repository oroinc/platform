<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;

/**
 * Describes interface for bulk insert query executors
 */
interface InsertQueryExecutorInterface
{
    /**
     * @throws QueryException
     * @throws \Doctrine\DBAL\Exception
     */
    public function execute(string $className, array $fields, QueryBuilder $selectQueryBuilder): int;
}
