<?php

namespace Oro\Bundle\ApiBundle\Util;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

/**
 * Provides a functionality to load an entity from the database.
 */
class EntityLoader
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * Loads an entity by its identifier.
     *
     * @param string              $entityClass The class name of an entity
     * @param mixed               $entityId    The identifier of an entity, can be a scalar or an array with
     *                                         the following schema: [identifier field name => value, ...]
     * @param EntityMetadata|null $metadata    The metadata that is used to adapt the given entity identifier
     *                                         to a criteria passed to "find" method of an entity manager
     *
     * @return object|null
     */
    public function findEntity(string $entityClass, mixed $entityId, EntityMetadata $metadata = null): ?object
    {
        $manager = $this->doctrine->getManagerForClass($entityClass);

        if (null === $metadata) {
            return $manager->getRepository($entityClass)->find($entityId);
        }

        $criteria = $this->buildFindCriteria($entityId, $metadata);
        if ($this->isEntityIdentifierEqualToPrimaryKey($criteria, $manager->getClassMetadata($entityClass))) {
            if (\is_array($entityId)) {
                $entityId = $criteria;
            }

            return $manager->getRepository($entityClass)->find($entityId);
        }

        $data = $manager->getRepository($entityClass)->findBy($criteria);
        if (empty($data)) {
            return null;
        }

        return reset($data);
    }

    private function buildFindCriteria(mixed $entityId, EntityMetadata $metadata): array
    {
        $criteria = [];
        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (!\is_array($entityId) && \count($idFieldNames) === 1) {
            $criteria[$metadata->getProperty(reset($idFieldNames))->getPropertyPath()] = $entityId;
        } else {
            foreach ($idFieldNames as $fieldName) {
                $criteria[$metadata->getProperty($fieldName)->getPropertyPath()] = $entityId[$fieldName];
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
