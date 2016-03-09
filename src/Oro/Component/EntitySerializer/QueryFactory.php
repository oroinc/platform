<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\DoctrineUtils\ORM\QueryUtils;
use Oro\Component\DoctrineUtils\ORM\SqlQueryBuilder;

class QueryFactory
{
    const FAKE_ID = '__fake_id__';

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
     * @param array $associationMapping
     * @param array $entityIds
     * @param array $config
     *
     * @return array [['entityId' => mixed, 'relatedEntityId' => mixed], ...]
     */
    public function getRelatedItemsIds($associationMapping, $entityIds, $config)
    {
        $limit = isset($config[ConfigUtil::MAX_RESULTS])
            ? $config[ConfigUtil::MAX_RESULTS]
            : -1;
        if ($limit > 0 && count($entityIds) > 1) {
            $subQuery = $this->getRelatedItemsIdsQuery($associationMapping, [self::FAKE_ID], $config);
            $subQuery->setMaxResults($limit);
            $qb = $this->getRelatedItemsUnionAllQuery(
                $this->doctrineHelper->getEntityManager($associationMapping['targetEntity']),
                $subQuery,
                $entityIds
            );
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
     * @param EntityManager    $em          The entity manager
     * @param Query            $subQueryTemplate
     * @param array            $entityIds
     *
     * @return SqlQueryBuilder
     */
    protected function getRelatedItemsUnionAllQuery(
        EntityManager $em,
        Query $subQueryTemplate,
        array $entityIds
    ) {
        // we should wrap all subqueries with brackets for PostgreSQL queries with UNION and LIMIT
        $subQuerySqlTemplate = '(' . QueryUtils::getExecutableSql($subQueryTemplate) . ')';

        $subQueries = [];
        foreach ($entityIds as $id) {
            $fakeId = self::FAKE_ID;
            if (!is_string($id)) {
                $fakeId = '\'' . $fakeId . '\'';
            }
            $subQueries[] = str_replace($fakeId, $id, $subQuerySqlTemplate);
        }

        $subQueryMapping = QueryUtils::parseQuery($subQueryTemplate)->getResultSetMapping();
        $selectStmt = sprintf(
            'entity.%s AS entityId, entity.%s AS relatedEntityId',
            QueryUtils::getColumnNameByAlias($subQueryMapping, 'entityId'),
            QueryUtils::getColumnNameByAlias($subQueryMapping, 'relatedEntityId')
        );

        $rsm = QueryUtils::createResultSetMapping($em->getConnection()->getDatabasePlatform());
        $rsm
            ->addScalarResult('entityId', 'entityId')
            ->addScalarResult('relatedEntityId', 'relatedEntityId');

        $qb = new SqlQueryBuilder($em, $rsm);
        $qb
            ->select($selectStmt)
            ->from('(' . implode(' UNION ALL ', $subQueries) . ')', 'entity');

        return $qb;
    }

    /**
     * @param array $associationMapping
     * @param array $entityIds
     * @param array $config
     *
     * @return Query
     */
    protected function getRelatedItemsIdsQuery($associationMapping, $entityIds, $config)
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
     * @param array        $config
     *
     * @return Query
     */
    public function getQuery(QueryBuilder $qb, $config)
    {
        $query = $qb->getQuery();
        $this->queryHintResolver->resolveHints(
            $query,
            ConfigUtil::getArrayValue($config, ConfigUtil::HINTS)
        );

        return $query;
    }
}
