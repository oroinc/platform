<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Compiles and executes "insert from select ... on conflict(field) do nothing" query
 */
class InsertFromSelectNoConflictQueryExecutor implements InsertQueryExecutorInterface
{
    private array $onConflictIgnoredFields = [];

    public function __construct(
        private NativeQueryExecutorHelper $helper
    ) {
    }

    public function setOnConflictIgnoredFields(array $onConflictIgnoredFields): void
    {
        $this->onConflictIgnoredFields = $onConflictIgnoredFields;
    }

    #[\Override]
    public function execute(string $className, array $fields, QueryBuilder $selectQueryBuilder): int
    {
        $insertToTableName = $this->helper->getTableName($className);
        $columns = $this->helper->getColumns($className, $fields);
        $selectQuery = $selectQueryBuilder->getQuery();

        $ignoredColumns = $this->onConflictIgnoredFields ?
            $this->helper->getColumns($className, $this->onConflictIgnoredFields) : [];

        $onConflictSqlPart = $ignoredColumns ?
            sprintf('ON CONFLICT(%s) DO NOTHING', implode(', ', $ignoredColumns)) : '';

        $sql = sprintf(
            'INSERT INTO %s (%s) %s %s',
            $insertToTableName,
            implode(', ', $columns),
            $selectQuery->getSQL(),
            $onConflictSqlPart
        );
        list($params, $types) = $this->helper->processParameterMappings($selectQuery);
        // No possibility to use createNativeQuery with rsm http://www.doctrine-project.org/jira/browse/DDC-962
        return $this->helper->getManager($className)->getConnection()->executeStatement($sql, $params, $types);
    }
}
