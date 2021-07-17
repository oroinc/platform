<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;

/**
 * Abstract query class with functionality to get various table information.
 */
abstract class AbstractTableInformationQuery extends ParametrizedMigrationQuery
{
    /**
     * Get query description info.
     */
    abstract protected function getInfo(): string;

    abstract public function doExecute(LoggerInterface $logger, bool $dryRun = false): void;

    /**
     * {@inheritdoc}
     */
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $logger->info($this->getInfo());
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getReferenceTables(string $tableName, ?LoggerInterface $logger): array
    {
        if ($this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $sql = $this->getPgSqlReferenceTableListQuery();
        } else {
            $sql = $this->getMySqlReferenceTableListQuery();
        }

        $params = ['table_name' => $tableName, 'db_name' => $this->connection->getDatabase()];
        $types = ['table_name' => Types::STRING, 'db_name' => Types::STRING];
        if ($logger) {
            $this->logQuery($logger, $sql, $params, $types);
        }

        return $this->connection->fetchAll($sql, $params, $types);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getTableForeignKeys(string $tableName): array
    {
        $listTableForeignKeysSQL = $this->getListTableForeignKeysSQL($tableName);

        return array_map(static function (array $columnData) {
            return array_change_key_case($columnData, CASE_LOWER);
        }, $this->connection->fetchAll($listTableForeignKeysSQL));
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getListTableForeignKeysSQL(string $tableName): string
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSqlPlatform) {
            return $this->getListTableForeignKeysPgSql($tableName);
        }

        return $platform->getListTableForeignKeysSQL($tableName);
    }

    /**
     * @param string $tableName
     * @return string
     */
    protected function getListTableForeignKeysPgSql(string $tableName)
    {
        return sprintf(
            "SELECT
                cu.constraint_name,
                cu.column_name,
                ccu.table_name as referenced_table_name,
                ccu.column_name as referenced_column_name
            FROM information_schema.table_constraints tc
            INNER JOIN information_schema.key_column_usage cu
                ON cu.constraint_name = tc.constraint_name
                AND cu.table_catalog = tc.table_catalog
                AND cu.table_name = tc.table_name
            INNER JOIN information_schema.constraint_column_usage ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_catalog = tc.table_catalog
            WHERE
                tc.table_name = '%s'
                AND tc.table_catalog = '%s'
                AND tc.constraint_type = 'FOREIGN KEY'",
            $tableName,
            $this->connection->getDatabase()
        );
    }

    protected function getPgSqlReferenceTableListQuery(): string
    {
        return 'SELECT 
                    r.table_name, 
                    r.column_name
                FROM information_schema.constraint_column_usage u
                INNER JOIN information_schema.referential_constraints fk
                    ON u.constraint_catalog = fk.unique_constraint_catalog 
                    AND u.constraint_schema = fk.unique_constraint_schema 
                    AND u.constraint_name = fk.unique_constraint_name 
                INNER JOIN information_schema.key_column_usage r
                    ON r.constraint_catalog = fk.constraint_catalog
                    AND r.constraint_schema = fk.constraint_schema
                    AND r.constraint_name = fk.constraint_name 
                WHERE 
                    u.table_name = :table_name
                    AND u.table_catalog = :db_name';
    }

    protected function getMySqlReferenceTableListQuery(): string
    {
        return 'SELECT 
                    rc.TABLE_NAME as table_name,
                    cu.COLUMN_NAME as column_name
                FROM information_schema.REFERENTIAL_CONSTRAINTS rc
                INNER JOIN information_schema.KEY_COLUMN_USAGE cu 
                    ON rc.CONSTRAINT_SCHEMA = cu.CONSTRAINT_SCHEMA
                    AND rc.TABLE_NAME = cu.TABLE_NAME
                    AND rc.CONSTRAINT_NAME = cu.CONSTRAINT_NAME
                WHERE 
                    rc.REFERENCED_TABLE_NAME = :table_name 
                    AND rc.CONSTRAINT_SCHEMA = :db_name';
    }

    /**
     * @param string $tableName
     * @param array|string[] $ignoredFields
     * @param LoggerInterface|null $logger
     * @return array|string[]
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getUniqueColumnNames(
        string $tableName,
        array $ignoredFields = ['id'],
        ?LoggerInterface $logger = null
    ): array {
        if ($this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
            $sql = $this->getPgSqlUniqueColumnNamesQuery();
            $params = [
                'namespace' => 'public',
                'table_name' => $tableName,
                'ignored_fields' => $ignoredFields,
            ];
            $types = [
                'namespace' => Types::STRING,
                'table_name' => Types::STRING,
                'ignored_fields' => Connection::PARAM_STR_ARRAY
            ];
        } else {
            $sql = $this->getMySqlUniqueColumnNamesQuery();
            $params = [
                'db_name' => $this->connection->getDatabase(),
                'table_name' => $tableName,
                'ignored_fields' => $ignoredFields,
            ];
            $types = [
                'db_name' => Types::STRING,
                'table_name' => Types::STRING,
                'ignored_fields' => Connection::PARAM_STR_ARRAY
            ];
        }

        if ($logger) {
            $this->logQuery($logger, $sql, $params, $types);
        }

        return $this->connection->fetchAll($sql, $params, $types);
    }

    protected function getPgSqlUniqueColumnNamesQuery(): string
    {
        return 'SELECT DISTINCT attr.attname as column_name
                FROM pg_index pgi
                JOIN pg_class idx on idx.oid = pgi.indexrelid
                JOIN pg_namespace insp on insp.oid = idx.relnamespace
                JOIN pg_class tbl on tbl.oid = pgi.indrelid
                JOIN pg_namespace tnsp on tnsp.oid = tbl.relnamespace
                JOIN pg_attribute attr on attr.attrelid = tbl.oid and attr.attnum = any(pgi.indkey)
                WHERE pgi.indisunique
                    AND tnsp.nspname = :namespace
                    AND tbl.relname = :table_name
                    AND attr.attname NOT IN (:ignored_fields)';
    }

    protected function getMySqlUniqueColumnNamesQuery(): string
    {
        return 'SELECT DISTINCT COLUMN_NAME as column_name
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = :db_name
                    AND TABLE_NAME = :table_name
                    AND NON_UNIQUE = 0
                    AND COLUMN_NAME not in (:ignored_fields)';
    }
}
