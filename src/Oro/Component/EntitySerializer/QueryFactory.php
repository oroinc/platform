<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;
use Oro\Component\DoctrineUtils\ORM\ResultSetMappingUtil;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;

/**
 * A set of methods to build queries used by the entity serializer to retrieve data.
 */
class QueryFactory
{
    /**
     * this value is used to optimize (avoid redundant call parseQuery) UNION ALL queries
     * for the getRelatedItemsIds() method
     */
    private const FAKE_ID = '__fake_id__';

    /** the maximum number of sub-queries in UNION query */
    private const UNION_QUERY_LIMIT = 100;

    private DoctrineHelper $doctrineHelper;
    private QueryResolver $queryResolver;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        QueryResolver $queryResolver
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->queryResolver = $queryResolver;
    }

    public function getQuery(QueryBuilder $qb, EntityConfig $config): Query
    {
        $query = $qb->getQuery();
        $this->queryResolver->resolveQuery($query, $config);

        $limit = $qb->getMaxResults();
        if (null !== $limit && $config->getHasMore() && $query->getMaxResults() === $limit) {
            $query->setMaxResults($limit + 1);
        }

        return $query;
    }

    public function getRelatedItemsQueryBuilder(string $entityClass, array $entityIds): QueryBuilder
    {
        $qb = $this->doctrineHelper->createQueryBuilder($entityClass, 'r');
        $entityIdField = $this->doctrineHelper->getEntityIdFieldName($entityClass);
        $this->applyEntityIdentifierRestriction($qb, 'r', $entityIdField, $entityIds);

        return $qb;
    }

    public function getToManyAssociationQueryBuilder(array $associationMapping, array $entityIds): QueryBuilder
    {
        $qb = $this->getNotInitializedToManyAssociationQueryBuilder($associationMapping);
        $this->initializeAssociationQueryBuilder($qb, $associationMapping['sourceEntity'], $entityIds);

        return $qb;
    }

    public function getNotInitializedToManyAssociationQueryBuilder(array $associationMapping): QueryBuilder
    {
        if (self::isOneToManyAssociation($associationMapping)) {
            return $this->doctrineHelper
                ->createQueryBuilder($associationMapping['targetEntity'], 'r')
                ->innerJoin('r.' . $associationMapping['mappedBy'], 'e');
        }

        return $this->doctrineHelper
            ->createQueryBuilder($associationMapping['sourceEntity'], 'e')
            ->innerJoin('e.' . $associationMapping['fieldName'], 'r');
    }

    public function getComplexToManyAssociationQueryBuilder(array $associationMappings, array $entityIds): QueryBuilder
    {
        $qb = $this->getNotInitializedComplexToManyAssociationQueryBuilder($associationMappings);
        $associationMapping = end($associationMappings);
        $this->initializeAssociationQueryBuilder($qb, $associationMapping['sourceEntity'], $entityIds);

        return $qb;
    }

    public function getNotInitializedComplexToManyAssociationQueryBuilder(array $associationMappings): QueryBuilder
    {
        if (\count($associationMappings) < 2) {
            throw new \LogicException('At least 2 association must be provided.');
        }

        $firstAssociationMapping = array_shift($associationMappings);
        $lastAssociationMapping = array_pop($associationMappings);

        if (self::isOneToManyAssociation($lastAssociationMapping)) {
            $qb = $this->doctrineHelper
                ->createQueryBuilder($lastAssociationMapping['targetEntity'], 'r')
                ->innerJoin('r.' . $lastAssociationMapping['mappedBy'], 'a1');
            $joinIndex = 1;
            foreach ($associationMappings as $mapping) {
                if (empty($mapping['inversedBy'])) {
                    throw new \RuntimeException(sprintf(
                        'Cannot build to-many association query because "inversedBy" option is empty'
                        . ' for "%s::%s" association. You need to set an association query manually.',
                        $mapping['sourceEntity'],
                        $mapping['fieldName']
                    ));
                }
                $qb->innerJoin(sprintf('a%d.%s', $joinIndex, $mapping['inversedBy']), 'a' . ($joinIndex + 1));
                $joinIndex++;
            }
            if (empty($firstAssociationMapping['inversedBy'])) {
                throw new \RuntimeException(sprintf(
                    'Cannot build to-many association query because "inversedBy" option is empty'
                    . ' for "%s::%s" association. You need to set an association query manually.',
                    $firstAssociationMapping['sourceEntity'],
                    $firstAssociationMapping['fieldName']
                ));
            }
            $qb->innerJoin(sprintf('a%d.%s', $joinIndex, $firstAssociationMapping['inversedBy']), 'e');

            return $qb;
        }

        $qb = $this->doctrineHelper
            ->createQueryBuilder($firstAssociationMapping['sourceEntity'], 'e')
            ->innerJoin('e.' . $firstAssociationMapping['fieldName'], 'a1');
        $joinIndex = 1;
        foreach ($associationMappings as $mapping) {
            $qb->innerJoin(sprintf('a%d.%s', $joinIndex, $mapping['fieldName']), 'a' . ($joinIndex + 1));
            $joinIndex++;
        }
        $qb->innerJoin(sprintf('a%d.%s', $joinIndex, $lastAssociationMapping['fieldName']), 'r');

        return $qb;
    }

    public function initializeAssociationQueryBuilder(
        QueryBuilder $qb,
        string $entityClass,
        array $entityIds,
        bool $forceIn = false
    ): void {
        $entityIdField = $this->doctrineHelper->getEntityIdFieldName($entityClass);
        $qb->select(sprintf('e.%s as entityId', $entityIdField));
        $this->applyEntityIdentifierRestriction($qb, 'e', $entityIdField, $entityIds, $forceIn);
    }

    /**
     * @param QueryBuilder $qb
     * @param string       $entityClass
     * @param string       $targetEntityClass
     * @param array        $entityIds
     * @param EntityConfig $config
     *
     * @return array [['entityId' => mixed, 'relatedEntityId' => mixed], ...]
     */
    public function getRelatedItemsIds(
        QueryBuilder $qb,
        string $entityClass,
        string $targetEntityClass,
        array $entityIds,
        EntityConfig $config
    ): array {
        $limit = $config->getMaxResults();
        if (null !== $limit && $config->getHasMore()) {
            $limit++;
        }

        if (null !== $limit && $limit > 0 && \count($entityIds) > 1) {
            $this->initializeAssociationQueryBuilder($qb, $entityClass, [self::FAKE_ID]);
            $query = $this->getRelatedItemsIdsQuery($qb, $targetEntityClass, $config);
            $rows = \count($entityIds) > self::UNION_QUERY_LIMIT
                ? $this->getRelatedItemsIdsBySeveralUnionAllQueries($query, $entityIds, $limit)
                : $this->getRelatedItemsUnionAllQuery($query, $entityIds, $limit)
                    ->getQuery()
                    ->getScalarResult();
        } else {
            $this->initializeAssociationQueryBuilder($qb, $entityClass, $entityIds);
            $query = $this->getRelatedItemsIdsQuery($qb, $targetEntityClass, $config);
            if (null !== $limit && $limit >= 0) {
                $query->setMaxResults($limit);
            }
            $rows = $query->getScalarResult();
        }

        return $rows;
    }

    private function getRelatedItemsIdsBySeveralUnionAllQueries(
        Query $query,
        array $entityIds,
        int $relatedRecordsLimit
    ): array {
        $rows = [];
        $chunkEntityIds = [];
        $chunkLimit = 0;
        foreach ($entityIds as $entityId) {
            $chunkEntityIds[] = $entityId;
            $chunkLimit++;
            if ($chunkLimit >= self::UNION_QUERY_LIMIT) {
                $chunkRows = $this->getRelatedItemsUnionAllQuery($query, $chunkEntityIds, $relatedRecordsLimit)
                    ->getQuery()
                    ->getScalarResult();
                foreach ($chunkRows as $row) {
                    $rows[] = $row;
                }
                $chunkEntityIds = [];
                $chunkLimit = 0;
            }
        }
        if ($chunkLimit > 0) {
            $chunkRows = $this->getRelatedItemsUnionAllQuery($query, $chunkEntityIds, $relatedRecordsLimit)
                ->getQuery()
                ->getScalarResult();
            foreach ($chunkRows as $row) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    private function getRelatedItemsUnionAllQuery(
        Query $subQueryTemplate,
        array $entityIds,
        int $relatedRecordsLimit
    ): SqlQueryBuilder {
        $subQueryTemplate->setMaxResults($relatedRecordsLimit);
        $parsedSubQuery = QueryUtil::parseQuery($subQueryTemplate);
        // we should wrap all subqueries with brackets for PostgreSQL queries with UNION and LIMIT
        $subQuerySqlTemplate = '(' . QueryUtil::getExecutableSql($subQueryTemplate, $parsedSubQuery) . ')';

        // we should build subquery for each parent entity id because the limit of related records
        // should by applied for each parent entity individually
        $subQueries = [];
        foreach ($entityIds as $id) {
            $fakeId = self::FAKE_ID;
            if (!\is_string($id)) {
                $fakeId = '\'' . $fakeId . '\'';
            }
            $subQueries[] = str_replace($fakeId, $id, $subQuerySqlTemplate);
        }

        $subQueryMapping = $parsedSubQuery->getResultSetMapping();
        $selectStmt = sprintf(
            'entity.%s AS entityId, entity.%s AS relatedEntityId',
            ResultSetMappingUtil::getColumnNameByAlias($subQueryMapping, 'entityId'),
            ResultSetMappingUtil::getColumnNameByAlias($subQueryMapping, 'relatedEntityId')
        );

        $rsm = ResultSetMappingUtil::createResultSetMapping(
            $subQueryTemplate->getEntityManager()->getConnection()->getDatabasePlatform()
        );
        $rsm
            ->addScalarResult('entityId', 'entityId')
            ->addScalarResult('relatedEntityId', 'relatedEntityId');

        $qb = new SqlQueryBuilder($subQueryTemplate->getEntityManager(), $rsm);
        $qb
            ->select($selectStmt)
            ->from('(' . implode(' UNION ALL ', $subQueries) . ')', 'entity');

        return $qb;
    }

    private function getRelatedItemsIdsQuery(QueryBuilder $qb, string $targetEntityClass, EntityConfig $config): Query
    {
        $qb->addSelect(sprintf(
            'r.%s as relatedEntityId',
            $this->doctrineHelper->getEntityIdFieldName($targetEntityClass)
        ));

        return $this->getQuery($qb, $config);
    }

    private function applyEntityIdentifierRestriction(
        QueryBuilder $qb,
        string $alias,
        string $entityIdField,
        array $entityIds,
        bool $forceIn = false
    ): void {
        if (!$forceIn && \count($entityIds) === 1) {
            $qb
                ->where(sprintf('%s.%s = :id', $alias, $entityIdField))
                ->setParameter('id', reset($entityIds));
        } else {
            $qb
                ->where(sprintf('%s.%s IN (:ids)', $alias, $entityIdField))
                ->setParameter('ids', $entityIds);
        }
    }

    private static function isOneToManyAssociation(array $associationMapping): bool
    {
        return $associationMapping['mappedBy'] && $associationMapping['type'] === ClassMetadata::ONE_TO_MANY;
    }
}
