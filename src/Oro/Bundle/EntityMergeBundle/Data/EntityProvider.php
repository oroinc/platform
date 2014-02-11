<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class EntityProvider
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
     * @param string $entityName
     * @return \Doctrine\ORM\EntityRepository
     * @throws InvalidArgumentException
     */
    protected function getEntityRepository($entityName)
    {
        $repository = $this->getRepository($entityName);

        if ($repository->getClassName() != $entityName) {
            throw new InvalidArgumentException('Incorrect repository returned');
        }

        return $repository;
    }

    /**
     * @param string $entityName
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository($entityName)
    {
        return $this->entityManager->getRepository($entityName);
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
     * @param string $entity
     * @return string
     */
    protected function getEntityIdentifierValue($entity)
    {
        $idValues = $this->entityManager->getClassMetadata(get_class($entity))->getIdentifierValues($entity);
        return current($idValues);
    }
}
