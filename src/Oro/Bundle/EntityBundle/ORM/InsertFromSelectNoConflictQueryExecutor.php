<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Compiles and executes "insert ignore from select" or "insert from select ... on conflict(field) do nothing" query
 */
class InsertFromSelectNoConflictQueryExecutor extends InsertFromSelectQueryExecutor
{
    private array $onConflictIgnoredFields = [];

    public function __construct(
        private NativeQueryExecutorHelper $helper
    ) {
        parent::__construct($helper);
    }

    public function setOnConflictIgnoredFields(array $onConflictIgnoredFields): void
    {
        $this->onConflictIgnoredFields = $onConflictIgnoredFields;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(string $className, array $fields, QueryBuilder $selectQueryBuilder): int
    {
        $connection = $this->helper->getManager($className)->getConnection();
        $insertToTableName = $this->helper->getTableName($className);
        $columns = $this->helper->getColumns($className, $fields);
        $selectQuery = $selectQueryBuilder->getQuery();
        $dbPlatformName = $connection->getDatabasePlatform()->getName();

        if ($dbPlatformName === 'postgresql') {
            $sql = $this->getPgsqlInsertFromSelectQuery($className, $insertToTableName, $columns, $selectQuery);
        } else {
            $sql = $this->getMysqlInsertFromSelectQuery($insertToTableName, $columns, $selectQuery);
        }

        [$params, $types] = $this->helper->processParameterMappings($selectQuery);
        // No possibility to use createNativeQuery with rsm http://www.doctrine-project.org/jira/browse/DDC-962
        return $connection->executeStatement($sql, $params, $types);
    }

    private function getMysqlInsertFromSelectQuery(
        string $insertToTableName,
        array $columns,
        Query $selectQuery
    ): string {
        return sprintf(
            'INSERT IGNORE INTO %s (%s) %s',
            $insertToTableName,
            implode(', ', $columns),
            $selectQuery->getSQL()
        );
    }

    private function getPgsqlInsertFromSelectQuery(
        string $className,
        string $insertToTableName,
        array $columns,
        Query $selectQuery
    ): string {
        $ignoredColumns = $this->onConflictIgnoredFields ?
            $this->helper->getColumns($className, $this->onConflictIgnoredFields) : [];

        $onConflictSqlPart = $ignoredColumns ?
            sprintf('ON CONFLICT(%s) DO NOTHING', implode(', ', $ignoredColumns)) : '';

        return sprintf(
            'INSERT INTO %s (%s) %s %s',
            $insertToTableName,
            implode(', ', $columns),
            $selectQuery->getSQL(),
            $onConflictSqlPart
        );
    }
}
