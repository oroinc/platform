<?php
/**
 * Created by PhpStorm.
 * User: Alexandr
 * Date: 2/10/14
 * Time: 5:34 PM
 */

namespace Oro\Bundle\EntityMergeBundle\DataGrid\Extension\MassAction\Actions\Merge;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;

class MergeEntitiesDataProvider
{

    /**
     * @var Registry
     */
    private $doctrineRegistry;

    /**
     * @param Registry $doctrine
     */
    public function setDoctrineRegistry(Registry $doctrine)
    {
        $this->doctrineRegistry = $doctrine;
    }

    /**
     * @param string $entityName
     * @return \Doctrine\ORM\EntityRepository
     * @throws InvalidArgumentException
     */
    public function getEntityRepository($entityName)
    {
        $repository = $this->getRepository($this->getEntityManager(), $entityName);

        if ($repository->getClassName() != $entityName) {
            throw new InvalidArgumentException('Incorrect repository returned');
        }

        return $repository;
    }

    /**
     * @param string $entityName
     * @param string $entityIdentifier
     * @param array $entityIds
     * @internal param $repository
     * @return mixed
     */
    public function getEntitiesByPk($entityName, $entityIdentifier, array $entityIds)
    {
        $repository = $this->getEntityRepository($entityName);

        $queryBuilder = $repository->createQueryBuilder('entity');

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
        return $this->getEntityManager()->getClassMetadata($className)->getSingleIdentifierFieldName();
    }

    /**
     * @param EntityManager $manager
     * @param string $entityName
     * @return \Doctrine\ORM\EntityRepository
     */
    protected function getRepository(EntityManager $manager, $entityName)
    {
        return $manager->getRepository($entityName);
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->doctrineRegistry->getManager();
    }
}
