<?php

namespace Oro\Bundle\EntityMergeBundle\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class DoctrineHelper
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Get entities by ids
     *
     * @param string $className
     * @param array $entityIds
     * @return object[]
     */
    public function getEntitiesByIds($className, array $entityIds)
    {
        $repository = $this->getEntityRepository($className);
        $queryBuilder = $repository->createQueryBuilder('entity');
        $entityIdentifier = $this->getEntityIdentifier($className);
        $identifierExpression = sprintf('entity.%s', $entityIdentifier);
        $queryBuilder->add('where', $queryBuilder->expr()->in($identifierExpression, $entityIds));
        $entities = $queryBuilder->getQuery()->execute();

        return $entities;
    }

    /**
     * @param string $entityName
     * @return EntityRepository
     * @throws InvalidArgumentException
     */
    public function getEntityRepository($entityName)
    {
        $repository = $this->entityManager->getRepository($entityName);

        if ($repository->getClassName() != $entityName) {
            throw new InvalidArgumentException('Incorrect repository returned');
        }

        return $repository;
    }

    /**
     * @param string $className
     * @return string
     */
    public function getEntityIdentifier($className)
    {
        return $this->entityManager->getClassMetadata($className)->getSingleIdentifierFieldName();
    }

    /**
     * Get list of entities ids
     *
     * @param array $entities
     * @return array
     */
    public function getEntityIds(array $entities)
    {
        $result = array();

        foreach ($entities as $entity) {
            $result[] = $this->getEntityIdentifierValue($entity);
        }

        return $result;
    }

    /**
     * @param string $entity
     * @return string
     */
    public function getEntityIdentifierValue($entity)
    {
        $idValues = $this->entityManager->getClassMetadata(get_class($entity))->getIdentifierValues($entity);
        return current($idValues);
    }

    /**
     * Checks if entities are equal
     *
     * @param object $entity
     * @param object $other
     * @return bool
     * @throws InvalidArgumentException
     */
    public function isEntityEqual($entity, $other)
    {
        if (!is_object($entity)) {
            throw new InvalidArgumentException(
                sprintf('$entity argument must be an object, "%s" given.', gettype($entity))
            );
        }

        if (!is_object($other)) {
            throw new InvalidArgumentException(
                sprintf('$other argument must be an object, "%s" given.', gettype($entity))
            );
        }

        $firstClass = ClassUtils::getRealClass($entity);
        $secondClass = ClassUtils::getRealClass($other);

        return
            $firstClass == $secondClass &&
            $this->getEntityIdentifierValue($entity) == $this->getEntityIdentifierValue($other);
    }

    /**
     * @param string $className
     * @return ClassMetadata
     */
    public function getDoctrineMetadataFor($className)
    {
        return $this
            ->getMetadataFactory()
            ->getMetadataFor($className);
    }

    /**
     * @return ClassMetadata[]
     */
    public function getAllMetadata()
    {
        return $this
            ->getMetadataFactory()
            ->getAllMetadata();
    }

    /**
     * @return \Doctrine\ORM\Mapping\ClassMetadataFactory
     */
    protected function getMetadataFactory()
    {
        return $this
            ->entityManager
            ->getMetadataFactory();
    }
}
