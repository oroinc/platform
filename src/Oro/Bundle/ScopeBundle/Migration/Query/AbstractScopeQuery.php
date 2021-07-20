<?php

namespace Oro\Bundle\ScopeBundle\Migration\Query;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\AbstractTableInformationQuery;

/**
 * Abstract query class with general logic for oro_scope manipulations.
 */
abstract class AbstractScopeQuery extends AbstractTableInformationQuery
{
    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getColumnNames(): array
    {
        $platform = $this->connection->getDatabasePlatform();
        $columnNames = array_map(static function (array $columnData) use ($platform) {
            return $platform->quoteSingleIdentifier($columnData['column_name']);
        }, $this->getTableForeignKeys('oro_scope'));

        sort($columnNames);

        return $columnNames;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getColumnsHashExpression(string $columnPrefix = null): string
    {
        if ($this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            return $this->getColumnsHashExpressionForPgSql($columnPrefix);
        }

        return $this->getColumnsHashExpressionForMySql($columnPrefix);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getColumnsHashExpressionForPgSql(string $columnPrefix = null): string
    {
        return 'MD5(' . implode(" || ':' || ", array_map(static function (string $name) use ($columnPrefix) {
            return "COALESCE({$columnPrefix}{$name}::text, '0')";
        }, $this->getColumnNames())) . ')';
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getColumnsHashExpressionForMySql(string $columnPrefix = null): string
    {
        return ' MD5(CONCAT(' . implode(", ':', ", array_map(static function (string $name) use ($columnPrefix) {
            return "COALESCE({$columnPrefix}{$name}, '0')";
        }, $this->getColumnNames())) . '))';
    }
}
