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
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function fillScopeHashesForExistingRows(
        LoggerInterface $logger,
        bool $dryRun
    ): void {
        $rowHashUpdateQuery = 'UPDATE oro_scope SET row_hash = ' . $this->getColumnsHashExpression();

        $this->logQuery($logger, $rowHashUpdateQuery);
        if (!$dryRun) {
            $this->connection->executeUpdate($rowHashUpdateQuery);
        }
    }

    /**
     * @param array $newToOldScopeIdMap
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function switchReferencesFromOldToNewScopes(
        array $newToOldScopeIdMap,
        LoggerInterface $logger,
        bool $dryRun
    ): void {
        $types = ['toId' => Types::INTEGER, 'fromIds' => Connection::PARAM_INT_ARRAY];

        $references = $this->getReferenceTables('oro_scope', $logger);
        foreach ($references as $reference) {
            $qb = $this->connection->createQueryBuilder();
            $qb->update($reference['table_name'])
                ->set($reference['column_name'], ':toId')
                ->where($qb->expr()->in($reference['column_name'], ':fromIds'));

            foreach ($newToOldScopeIdMap as $toId => $fromIds) {
                $params = ['toId' => $toId, 'fromIds' => $fromIds];

                $this->logQuery($logger, $qb->getSQL(), $params, $types);
                if (!$dryRun) {
                    $qb->setParameters($params, $types)->execute();
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
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
     * @param array $newToOldScopeIdMap
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeScopeDuplicates(array $newToOldScopeIdMap, LoggerInterface $logger, bool $dryRun): void
    {
        $types = ['ids' => Connection::PARAM_INT_ARRAY];
        $params = ['ids' => array_merge(...$newToOldScopeIdMap)];

        $deleteSql = 'DELETE FROM oro_scope WHERE id IN (:ids)';

        $this->logQuery($logger, $deleteSql, $params, $types);
        if (!$dryRun) {
            $this->connection->executeUpdate($deleteSql, $params, $types);
        }
    }
}
