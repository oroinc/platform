<?php

namespace Oro\Bundle\EntityMergeBundle\Data;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class EntityProvider
{
    /**
     * @var Registry
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
     * @param string $entityName
     * @return \Doctrine\ORM\EntityRepository
     * @throws InvalidArgumentException
     */
    public function getEntityRepository($entityName)
    {
        $repository = $this->getRepository($entityName);

        if ($repository->getClassName() != $entityName) {
            throw new InvalidArgumentException('Incorrect repository returned');
        }

        return $repository;
    }

    /**
     * @param string $entityName
     * @param array $entityIds
     * @return mixed
     */
    public function getEntitiesByIds($entityName, array $entityIds)
    {
        $repository = $this->getEntityRepository($entityName);

        $queryBuilder = $repository->createQueryBuilder('entity');

        $entityIdentifier = $this->getEntityIdentifier($entityName);

        $identifierExpression = sprintf('entity.%s', $entityIdentifier);

        $queryBuilder->add('where', $queryBuilder->expr()->in($identifierExpression, $entityIds));

        $entities = $queryBuilder->getQuery()->execute();
        return $entities;
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
     * @param string $entityName
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository($entityName)
    {
        return $this->entityManager->getRepository($entityName);
    }

}
