<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Compiles and executes "insert from select ... on conflict(field) do nothing" query
 * needs for avoiding BC type break
 */
class InsertNoConflictQueryExecutor extends InsertFromSelectQueryExecutor implements
    InsertNoConflictQueryExecutorInterface
{
    public function __construct(
        NativeQueryExecutorHelper $helper,
        private InsertFromSelectNoConflictQueryExecutor $queryExecutor
    ) {
        parent::__construct($helper);
    }

    /**
     * {@inheritDoc}
     */
    public function setOnConflictIgnoredFields(array $onConflictIgnoredFields): void
    {
        $this->queryExecutor->setOnConflictIgnoredFields($onConflictIgnoredFields);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(string $className, array $fields, QueryBuilder $selectQueryBuilder): int
    {
        return $this->queryExecutor->execute($className, $fields, $selectQueryBuilder);
    }
}
