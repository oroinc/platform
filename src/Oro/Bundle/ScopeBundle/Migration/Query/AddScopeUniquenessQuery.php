<?php

namespace Oro\Bundle\ScopeBundle\Migration\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Psr\Log\LoggerInterface;

/**
 * Execute SQL query for remove duplicate scopes and filling row has column.
 * Also, execute SQL query for substitution all duplicate references ids on actual.
 */
class AddScopeUniquenessQuery extends AbstractScopeQuery
{
    /**
     * {@inheritDoc}
     */
    protected function getInfo(): string
    {
        return 'Add unique index to oro_scope row_hash';
    }

    /**
     * {@inheritDoc}
     */
    public function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $this->fillScopeHashesForExistingRows($logger, $dryRun);
        $newToOldScopeIdMap = $this->getNewToOldScopeIdMapping($logger);

        if ($newToOldScopeIdMap) {
            $this->switchReferencesFromOldToNewScopes($newToOldScopeIdMap, $logger, $dryRun);
            $this->removeScopeDuplicates($newToOldScopeIdMap, $logger, $dryRun);
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function fillScopeHashesForExistingRows(
        LoggerInterface $logger,
        bool $dryRun
    ): void {
        $rowHashUpdateQuery = 'UPDATE oro_scope SET row_hash = ' . $this->getColumnsHashExpression();

        $this->logQuery($logger, $rowHashUpdateQuery);
        if (!$dryRun) {
            $this->connection->executeStatement($rowHashUpdateQuery);
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function switchReferencesFromOldToNewScopes(
        array $newToOldScopeIdMap,
        LoggerInterface $logger,
        bool $dryRun
    ): void {
        $references = $this->getReferenceTables('oro_scope', $logger);

        foreach ($references as $reference) {
            $this->removeDuplicatesForUniqueRecords(
                $reference['table_name'],
                $reference['column_name'],
                $newToOldScopeIdMap,
                $logger,
                $dryRun
            );
            $this->migrateDuplicateScopes(
                $reference['table_name'],
                $reference['column_name'],
                $newToOldScopeIdMap,
                $logger,
                $dryRun
            );
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getNewToOldScopeIdMapping(LoggerInterface $logger): array
    {
        $duplicateSql = 'SELECT MIN(id) FROM oro_scope GROUP BY row_hash HAVING COUNT(id) > 1';
        $this->logQuery($logger, $duplicateSql);

        $minimumIds = $this->connection->executeQuery($duplicateSql)->fetchAll(\PDO::FETCH_COLUMN);
        if (!$minimumIds) {
            return [];
        }

        $duplicateGroupSql = 'SELECT 
                s1.id to_id, 
                s2.id from_id 
            FROM oro_scope s1 
            INNER JOIN oro_scope s2
                ON s1.row_hash = s2.row_hash AND s1.id < s2.id AND s1.id IN (:minimumIds)';

        $types = ['minimumIds' => Connection::PARAM_INT_ARRAY];
        $params = ['minimumIds' => $minimumIds];
        $this->logQuery($logger, $duplicateGroupSql, $params, $types);

        $scopes = $this->connection->fetchAll($duplicateGroupSql, $params, $types);

        $newToOldScopeIdMap = [];
        foreach ($scopes as $scope) {
            $newToOldScopeIdMap[$scope['to_id']][] = $scope['from_id'];
        }

        return $newToOldScopeIdMap;
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeScopeDuplicates(array $newToOldScopeIdMap, LoggerInterface $logger, bool $dryRun): void
    {
        $types = ['ids' => Connection::PARAM_INT_ARRAY];
        $params = ['ids' => array_merge(...$newToOldScopeIdMap)];

        $deleteSql = 'DELETE FROM oro_scope WHERE id IN (:ids)';

        $this->logQuery($logger, $deleteSql, $params, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($deleteSql, $params, $types);
        }
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeDuplicatesForUniqueRecords(
        string $tableName,
        string $columnName,
        array $newToOldScopeIdMap,
        LoggerInterface $logger,
        bool $dryRun
    ): void {
        $uniqueColumnNames = $this->getUniqueColumnNames($tableName, ['id', $columnName], $logger);
        if ($uniqueColumnNames) {
            foreach ($newToOldScopeIdMap as $toId => $fromIds) {
                $types = [
                    'oldScopeId' => Connection::PARAM_INT_ARRAY,
                    'newScopeId' => Types::INTEGER,
                ];
                $params = ['oldScopeId' => $fromIds, 'newScopeId' => $toId];

                $deleteSql = $this->getUpdateScopeQuery($tableName, $columnName, $uniqueColumnNames);

                $this->logQuery($logger, $deleteSql, $params, $types);
                if (!$dryRun) {
                    $this->connection->executeQuery($deleteSql, $params, $types);
                }
            }
        };
    }

    /**
     * @param string $tableName
     * @param string $columnName
     * @param array $uniqueColumnNames
     * @return string
     */
    protected function getUpdateScopeQuery(
        string $tableName,
        string $columnName,
        array $uniqueColumnNames
    ) {
        $deleteSql = sprintf(
            'delete from %s where %s in (:newScopeId, :oldScopeId)',
            $tableName,
            $columnName
        );

        foreach ($uniqueColumnNames as $uniqueColumnName) {
            // double nested selects is a workaround for mysql not being able to delete from a table
            // that is used in a sub-query
            $deleteSql .= sprintf(
                // Process only records that have duplicates and may lead to unique constraint violation
                'AND %2$s.%1$s in (SELECT t1.%1$s from (
                    SELECT %2$s.%1$s FROM %2$s
                    WHERE %3$s in (:newScopeId, :oldScopeId)
                    GROUP BY %2$s.%1$s
                    HAVING COUNT(%2$s.%1$s) > 1
                ) as t1)'
                // Skip one record among all duplicate scopes that will be used as a new and only one scope + record
                . 'AND (%2$s.%1$s, %3$s) not in (SELECT t2.%1$s, scope_id from (
                    SELECT %2$s.%1$s, MIN(%3$s) as scope_id FROM %2$s
                    WHERE %3$s in (:newScopeId, :oldScopeId)
                    GROUP BY %2$s.%1$s
                    HAVING COUNT(%2$s.%1$s) > 1
                ) as t2)',
                $uniqueColumnName['column_name'],
                $tableName,
                $columnName
            );
        }

        /**
         * Example of the resulting query: for oro_cus_product_visibility table
         *
         * DELETE FROM oro_cus_product_visibility
         * WHERE scope_id in (:newScopeId, :oldScopeId)
         * AND product_id in (SELECT product_id from (
         *     SELECT product_id FROM oro_cus_product_visibility
         *     WHERE scope_id in (:newScopeId, :oldScopeId)
         *     GROUP BY product_id
         *     HAVING COUNT(product_id) > 1
         * ) as t1)
         * AND (product_id, scope_id) not in (SELECT product_id, scope_id from (
         *     SELECT product_id, MIN(scope_id) as scope_id FROM oro_cus_product_visibility
         *     WHERE scope_id in (:newScopeId, :oldScopeId)
         *     GROUP BY product_id
         *     HAVING COUNT(product_id) > 1
         * ) as t2)
         */

        return $deleteSql;
    }

    protected function migrateDuplicateScopes(
        string $tableName,
        string $columnName,
        array $newToOldScopeIdMap,
        LoggerInterface $logger,
        bool $dryRun
    ): void {
        $types = ['toId' => Types::INTEGER, 'fromIds' => Connection::PARAM_INT_ARRAY];
        $qb = $this->connection->createQueryBuilder();
        $qb->update($tableName)
            ->set($columnName, ':toId')
            ->where($qb->expr()->in($columnName, ':fromIds'));

        foreach ($newToOldScopeIdMap as $toId => $fromIds) {
            $params = ['toId' => $toId, 'fromIds' => $fromIds];

            $this->logQuery($logger, $qb->getSQL(), $params, $types);
            if (!$dryRun) {
                $qb->setParameters($params, $types)->execute();
            }
        }
    }
}
