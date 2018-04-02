<?php

namespace Oro\Bundle\EntityBundle\ORM\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Repository\RepositoryFactory;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;

class EntityRepositoryFactory implements RepositoryFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * ['<entityName>' => '<serviceId>', ...]
     *
     * @var array
     */
    protected $repositoryServiceIds = [];

    /**
     * ['<repositoryHash>' => <repositoryService>, ...]
     *
     * @var EntityRepository[]
     */
    protected $repositoryServices = [];

    /**
     * @param ContainerInterface $container
     * @param array $repositoryServices
     */
    public function __construct(ContainerInterface $container, array $repositoryServices)
    {
        $this->container = $container;
        $this->repositoryServiceIds = $repositoryServices;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $entityName = $entityManager->getClassMetadata($entityName)->getName();
        $repositoryHash = $entityName . spl_object_hash($entityManager);

        if (array_key_exists($repositoryHash, $this->repositoryServices)) {
            $repository = $this->repositoryServices[$repositoryHash];
        } elseif (array_key_exists($entityName, $this->repositoryServiceIds)) {
            $repositoryServiceId = $this->repositoryServiceIds[$entityName];
            $repository = $this->container->get($repositoryServiceId);
            $this->validateRepository($entityManager, $repository, $entityName);
            $this->repositoryServices[$repositoryHash] = $repository;
        } else {
            $repository = $this->getDefaultRepository($entityName, null, $entityManager);
            $this->repositoryServices[$repositoryHash] = $repository;
        }

        return $repository;
    }

    /**
     * @param string $entityName
     * @param string|null $repositoryClassName
     * @param EntityManagerInterface|null $entityManager
     * @return EntityRepository
     * @throws NotManageableEntityException
     */
    public function getDefaultRepository(
        $entityName,
        $repositoryClassName = null,
        EntityManagerInterface $entityManager = null
    ) {
        if (!$entityManager) {
            $entityManager = $this->getDefaultEntityManager($entityName);
        }

        if (!$repositoryClassName) {
            $repositoryClassName = $this->getDefaultRepositoryClassName($entityManager, $entityName);
        }

        return new $repositoryClassName($entityManager, $entityManager->getClassMetadata($entityName));
    }

    /**
     * Removes instances of all already loaded repositories.
     */
    public function clear()
    {
        $this->repositoryServices = [];
    }

    /**
     * @param string $entityName
     * @return EntityManagerInterface
     * @throws NotManageableEntityException
     */
    protected function getDefaultEntityManager($entityName)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine')->getManagerForClass($entityName);
        if (!$entityManager) {
            throw new NotManageableEntityException($entityName);
        }

        return $entityManager;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param string $entityName
     * @return string
     */
    protected function getDefaultRepositoryClassName(EntityManagerInterface $entityManager, $entityName)
    {
        return $entityManager->getClassMetadata($entityName)->customRepositoryClassName
            ?: $entityManager->getConfiguration()->getDefaultRepositoryClassName();
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param object $repository
     * @param string $entityName
     * @throws LogicException
     */
    protected function validateRepository(EntityManagerInterface $entityManager, $repository, $entityName)
    {
        $repositoryClassName = $this->getDefaultRepositoryClassName($entityManager, $entityName);

        if (!$repository instanceof EntityRepository) {
            throw new LogicException(
                sprintf('Repository for class %s must be instance of EntityRepository', $entityName)
            );
        }

        if (!is_a($repository, $repositoryClassName)) {
            throw new LogicException(
                sprintf('Repository for class %s must be instance of %s', $entityName, $repositoryClassName)
            );
        }
    }
}
