<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityIdMetadataInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Provides a functionality to load an entity from the database.
 */
class EntityLoader
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Loads an entity by its identifier.
     *
     * @param string                         $entityClass The class name of an entity
     * @param mixed                          $entityId    The identifier of an entity, can be a scalar or an array with
     *                                                    the following schema: [identifier field name => value, ...]
     * @param EntityIdMetadataInterface|null $metadata    The metadata that is used to adapt the given entity identifier
     *                                                    to a criteria passed to "find" method of an entity manager
     *
     * @return object|null
     *
     * @throws NonUniqueResultException when more than one entity was found
     */
    public function findEntity(string $entityClass, mixed $entityId, ?EntityIdMetadataInterface $metadata): ?object
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManagerForClass($entityClass);

        if (null === $metadata) {
            return $em->find($entityClass, $entityId);
        }

        $criteria = $this->buildFindCriteria($entityId, $metadata);
        if ($this->isEntityIdentifierEqualToPrimaryKey($criteria, $em->getClassMetadata($entityClass))) {
            if (\is_array($entityId)) {
                $entityId = $criteria;
            }

            return $em->find($entityClass, $entityId);
        }

        $qb = $this->doctrineHelper->createQueryBuilder($entityClass, 'e');
        foreach ($criteria as $fieldName => $fieldValue) {
            $qb->andWhere(QueryBuilderUtil::sprintf('e.%1$s = :%1$s', $fieldName));
            $qb->setParameter($fieldName, $fieldValue);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    private function buildFindCriteria(mixed $entityId, EntityIdMetadataInterface $metadata): array
    {
        $criteria = [];
        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (!\is_array($entityId) && \count($idFieldNames) === 1) {
            $criteria[$metadata->getPropertyPath($idFieldNames[0])] = $entityId;
        } else {
            foreach ($idFieldNames as $fieldName) {
                $criteria[$metadata->getPropertyPath($fieldName)] = $entityId[$fieldName];
            }
        }

        return $criteria;
    }

    private function isEntityIdentifierEqualToPrimaryKey(array $criteria, ClassMetadata $classMetadata): bool
    {
        $primaryKeys = $classMetadata->getIdentifierFieldNames();
        if (\count($primaryKeys) !== \count($criteria)) {
            return false;
        }

        $result = true;
        foreach ($primaryKeys as $fieldName) {
            if (!isset($criteria[$fieldName])) {
                $result = false;
                break;
            }
        }

        return $result;
    }
}
