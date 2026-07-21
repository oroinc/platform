<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Compiles and executes "insert from select ... on conflict(field) do nothing" query needs for avoid BC break
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

    #[\Override]
    public function setOnConflictIgnoredFields(array $onConflictIgnoredFields): void
    {
        $this->queryExecutor->setOnConflictIgnoredFields($onConflictIgnoredFields);
    }

    #[\Override]
    public function execute(string $className, array $fields, QueryBuilder $selectQueryBuilder): int
    {
        return $this->queryExecutor->execute($className, $fields, $selectQueryBuilder);
    }
}
