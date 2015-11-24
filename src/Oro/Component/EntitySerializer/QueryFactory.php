<?php

namespace Oro\Component\EntitySerializer;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;

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
            ->select(sprintf('e.%s as entityId', $entityIdField))
            ->where(sprintf('e.%s IN (:ids)', $entityIdField))
            ->setParameter('ids', $entityIds);
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
