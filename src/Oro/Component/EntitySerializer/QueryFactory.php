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

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var QueryResolver */
    private $queryResolver;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        QueryResolver $queryResolver
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->queryResolver = $queryResolver;
    }

    /**
     * @param QueryBuilder $qb
     * @param EntityConfig $config
     *
     * @return Query
     */
    public function getQuery(QueryBuilder $qb, EntityConfig $config)
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
        $this->initializeToManyAssociationQueryBuilder($qb, $associationMapping['sourceEntity'], $entityIds);

        return $qb;
    }

    public function getNotInitializedToManyAssociationQueryBuilder(array $associationMapping): QueryBuilder
    {
        if ($associationMapping['mappedBy'] && $associationMapping['type'] === ClassMetadata::ONE_TO_MANY) {
            return $this->doctrineHelper
                ->createQueryBuilder($associationMapping['targetEntity'], 'r')
                ->innerJoin('r.' . $associationMapping['mappedBy'], 'e');
        }

        return $this->doctrineHelper
            ->createQueryBuilder($associationMapping['sourceEntity'], 'e')
            ->innerJoin('e.' . $associationMapping['fieldName'], 'r');
    }

    public function initializeToManyAssociationQueryBuilder(
        QueryBuilder $qb,
        string $entityClass,
        array $entityIds
    ): void {
        $entityIdField = $this->doctrineHelper->getEntityIdFieldName($entityClass);
        $qb->select(\sprintf('e.%s as entityId', $entityIdField));
        $this->applyEntityIdentifierRestriction($qb, 'e', $entityIdField, $entityIds);
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
            $this->initializeToManyAssociationQueryBuilder($qb, $entityClass, [self::FAKE_ID]);
            $query = $this->getRelatedItemsIdsQuery($qb, $targetEntityClass, $config);
            $rows = $this->getRelatedItemsUnionAllQuery($query, $entityIds, $limit)
                ->getQuery()
                ->getScalarResult();
        } else {
            $this->initializeToManyAssociationQueryBuilder($qb, $entityClass, $entityIds);
            $query = $this->getRelatedItemsIdsQuery($qb, $targetEntityClass, $config);
            if (null !== $limit && $limit >= 0) {
                $query->setMaxResults($limit);
            }
            $rows = $query->getScalarResult();
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
            $subQueries[] = \str_replace($fakeId, $id, $subQuerySqlTemplate);
        }

        $subQueryMapping = $parsedSubQuery->getResultSetMapping();
        $selectStmt = \sprintf(
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
            ->from('(' . \implode(' UNION ALL ', $subQueries) . ')', 'entity');

        return $qb;
    }

    private function getRelatedItemsIdsQuery(QueryBuilder $qb, string $targetEntityClass, EntityConfig $config): Query
    {
        $qb->addSelect(\sprintf(
            'r.%s as relatedEntityId',
            $this->doctrineHelper->getEntityIdFieldName($targetEntityClass)
        ));

        return $this->getQuery($qb, $config);
    }

    private function applyEntityIdentifierRestriction(
        QueryBuilder $qb,
        string $alias,
        string $entityIdField,
        array $entityIds
    ): void {
        if (\count($entityIds) === 1) {
            $qb
                ->where(\sprintf('%s.%s = :id', $alias, $entityIdField))
                ->setParameter('id', \reset($entityIds));
        } else {
            $qb
                ->where(\sprintf('%s.%s IN (:ids)', $alias, $entityIdField))
                ->setParameter('ids', $entityIds);
        }
    }
}
