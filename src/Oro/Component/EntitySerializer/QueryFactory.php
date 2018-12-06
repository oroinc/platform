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

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param QueryResolver  $queryResolver
     */
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

    /**
     * @param string $entityClass
     * @param array  $entityIds
     *
     * @return QueryBuilder
     */
    public function getRelatedItemsQueryBuilder($entityClass, $entityIds)
    {
        return $this->doctrineHelper->createQueryBuilder($entityClass, 'r')
            ->where(sprintf('r.%s IN (:ids)', $this->doctrineHelper->getEntityIdFieldName($entityClass)))
            ->setParameter('ids', $entityIds);
    }

    /**
     * @param array $associationMapping
     * @param array $entityIds
     *
     * @return QueryBuilder
     */
    public function getToManyAssociationQueryBuilder($associationMapping, $entityIds)
    {
        $entityIdField = $this->doctrineHelper->getEntityIdFieldName($associationMapping['sourceEntity']);

        $qb = $this->doctrineHelper->createQueryBuilder($associationMapping['targetEntity'], 'r')
            ->select(sprintf('e.%s as entityId', $entityIdField));
        if (count($entityIds) === 1) {
            $qb
                ->where(sprintf('e.%s = :id', $entityIdField))
                ->setParameter('id', reset($entityIds));
        } else {
            $qb
                ->where(sprintf('e.%s IN (:ids)', $entityIdField))
                ->setParameter('ids', $entityIds);
        }
        if ($associationMapping['mappedBy'] && $associationMapping['type'] === ClassMetadata::ONE_TO_MANY) {
            $qb->innerJoin(
                $associationMapping['sourceEntity'],
                'e',
                'WITH',
                sprintf('r.%s = e', $associationMapping['mappedBy'])
            );
        } else {
            $qb->innerJoin(
                $associationMapping['sourceEntity'],
                'e',
                'WITH',
                sprintf('r MEMBER OF e.%s', $associationMapping['fieldName'])
            );
        }

        return $qb;
    }

    /**
     * @param array        $associationMapping
     * @param array        $entityIds
     * @param EntityConfig $config
     *
     * @return array [['entityId' => mixed, 'relatedEntityId' => mixed], ...]
     */
    public function getRelatedItemsIds($associationMapping, $entityIds, EntityConfig $config)
    {
        $limit = $config->getMaxResults();
        if (null !== $limit && $config->getHasMore()) {
            $limit++;
        }

        if ($limit > 0 && count($entityIds) > 1) {
            $rows = $this->getRelatedItemsUnionAllQuery($associationMapping, $entityIds, $config, $limit)
                ->getQuery()
                ->getScalarResult();
        } else {
            $query = $this->getRelatedItemsIdsQuery($associationMapping, $entityIds, $config);
            if ($limit >= 0) {
                $query->setMaxResults($limit);
            }
            $rows = $query->getScalarResult();
        }

        return $rows;
    }

    /**
     * @param array        $associationMapping
     * @param array        $entityIds
     * @param EntityConfig $config
     * @param int          $relatedRecordsLimit
     *
     * @return SqlQueryBuilder
     * @throws Query\QueryException
     */
    private function getRelatedItemsUnionAllQuery(
        $associationMapping,
        array $entityIds,
        EntityConfig $config,
        $relatedRecordsLimit
    ) {
        $subQueryTemplate = $this->getRelatedItemsIdsQuery($associationMapping, [self::FAKE_ID], $config);
        $subQueryTemplate->setMaxResults($relatedRecordsLimit);
        $parsedSubQuery = QueryUtil::parseQuery($subQueryTemplate);
        // we should wrap all subqueries with brackets for PostgreSQL queries with UNION and LIMIT
        $subQuerySqlTemplate = '(' . QueryUtil::getExecutableSql($subQueryTemplate, $parsedSubQuery) . ')';

        // we should build subquery for each parent entity id because the limit of related records
        // should by applied for each parent entity individually
        $subQueries = [];
        foreach ($entityIds as $id) {
            $fakeId = self::FAKE_ID;
            if (!is_string($id)) {
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

    /**
     * @param array        $associationMapping
     * @param array        $entityIds
     * @param EntityConfig $config
     *
     * @return Query
     */
    private function getRelatedItemsIdsQuery($associationMapping, $entityIds, EntityConfig $config)
    {
        $qb = $this->getToManyAssociationQueryBuilder($associationMapping, $entityIds)
            ->addSelect(
                sprintf(
                    'r.%s as relatedEntityId',
                    $this->doctrineHelper->getEntityIdFieldName($associationMapping['targetEntity'])
                )
            );

        return $this->getQuery($qb, $config);
    }
}
