<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\ResultSetMapping;

use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;

class QueryFactory
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var QueryHintResolverInterface */
    protected $queryHintResolver;

    /**
     * @param DoctrineHelper             $doctrineHelper
     * @param QueryHintResolverInterface $queryHintResolver
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        QueryHintResolverInterface $queryHintResolver
    ) {
        $this->doctrineHelper    = $doctrineHelper;
        $this->queryHintResolver = $queryHintResolver;
    }

    /**
     * @param string $entityClass
     * @param array  $entityIds
     *
     * @return QueryBuilder
     */
    public function getRelatedItemsQueryBuilder($entityClass, $entityIds)
    {
        return $this->doctrineHelper->getEntityRepository($entityClass)
            ->createQueryBuilder('r')
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

        $qb = $this->doctrineHelper->getEntityRepository($associationMapping['targetEntity'])
            ->createQueryBuilder('r')
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
        if (null !== $limit && count($entityIds) > 1) {
            $selectStmt = null;
            $subQueries = [];
            $subQuery = $this->getRelatedItemsIdsQuery($associationMapping, $entityIds, $config);
            $subQuery->setMaxResults($limit);
            // We should wrap all subqueries with brackets for PostgreSQL queries with UNION and LIMIT
            $subQueries[] = '(' . QueryUtils::getExecutableSql($subQuery) . ')';
            if (null === $selectStmt) {
                $mapping    = QueryUtils::parseQuery($subQuery)->getResultSetMapping();
                $selectStmt = sprintf(
                    'entity.%s AS entityId, entity.%s AS relatedEntityId',
                    QueryUtils::getColumnNameByAlias($mapping, 'entityId'),
                    QueryUtils::getColumnNameByAlias($mapping, 'relatedEntityId')
                );
            }
            $rsm = new ResultSetMapping();
            $rsm
                ->addScalarResult('entityId', 'entityId')
                ->addScalarResult('relatedEntityId', 'relatedEntityId');
            $qb = new SqlQueryBuilder(
                $this->doctrineHelper->getEntityManager($associationMapping['targetEntity']),
                $rsm
            );
            $qb
                ->select($selectStmt)
                ->from('(' . implode(' UNION ALL ', $subQueries) . ')', 'entity');
            $rows = $qb->getQuery()->getScalarResult();
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
     *
     * @return Query
     */
    protected function getRelatedItemsIdsQuery($associationMapping, $entityIds, EntityConfig $config)
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

    /**
     * @param QueryBuilder $qb
     * @param EntityConfig $config
     *
     * @return Query
     */
    public function getQuery(QueryBuilder $qb, EntityConfig $config)
    {
        $query = $qb->getQuery();
        $this->queryHintResolver->resolveHints($query, $config->getHints());

        return $query;
    }
}
