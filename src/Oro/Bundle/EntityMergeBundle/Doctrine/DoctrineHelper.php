<?php

namespace Oro\Bundle\EntityMergeBundle\Doctrine;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\Mapping\AdditionalMetadataProvider;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

/**
 * Doctrine helper methods for EntityMerge
 */
class DoctrineHelper
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private AdditionalMetadataProvider $additionalMetadataProvider
    ) {
    }

    /**
     * @return object[]
     */
    public function getEntitiesByIds(string $className, array $entityIds): array
    {
        if (!$entityIds) {
            return [];
        }

        return $this->getEntityRepository($className)
            ->createQueryBuilder('entity')
            ->where(\sprintf('entity.%s IN (:entityIds)', $this->getSingleIdentifierFieldName($className)))
            ->setParameter('entityIds', $entityIds)
            ->getQuery()
            ->execute();
    }

    public function getEntityRepository(string $entityName): EntityRepository
    {
        return $this->getEntityManager()->getRepository($entityName);
    }

    public function getSingleIdentifierFieldName(string $className): string
    {
        return $this->getEntityManager()->getClassMetadata($className)->getSingleIdentifierFieldName();
    }

    public function getEntityIds(array $entities): array
    {
        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->getEntityIdentifierValue($entity);
        }

        return $result;
    }

    public function getEntityIdentifierValue(object $entity): mixed
    {
        $idValues = $this->getMetadataFor(ClassUtils::getClass($entity))->getIdentifierValues($entity);
        if (\count($idValues) > 1) {
            throw new InvalidArgumentException(\sprintf(
                'An entity with composite ID is not supported. Entity: %s.',
                ClassUtils::getClass($entity)
            ));
        }

        return current($idValues);
    }

    public function isEntityEqual(object $entity, object $other): bool
    {
        return
            ClassUtils::getClass($entity) === ClassUtils::getClass($other)
            && $this->getEntityIdentifierValue($entity) == $this->getEntityIdentifierValue($other);
    }

    public function getInversedUnidirectionalAssociationMappings(string $className): array
    {
        return $this->additionalMetadataProvider->getInversedUnidirectionalAssociationMappings($className);
    }

    public function getMetadataFor(string $className): ClassMetadata
    {
        return $this->getMetadataFactory()->getMetadataFor($className);
    }

    /**
     * @return ClassMetadata[]
     */
    public function getAllMetadata(): array
    {
        return $this->getMetadataFactory()->getAllMetadata();
    }

    private function getMetadataFactory(): ClassMetadataFactory
    {
        return $this->getEntityManager()->getMetadataFactory();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->doctrine->getManager();
    }
}
