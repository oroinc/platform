<?php

namespace Oro\Bundle\EntityBundle\Handler;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The base class for classes that that provides an extended business logic to delete an entity.
 */
abstract class AbstractEntityDeleteHandlerExtension implements EntityDeleteHandlerExtensionInterface
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EntityDeleteAccessDeniedExceptionFactory */
    private $accessDeniedExceptionFactory;

    public function setDoctrine(ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
    }

    public function setAccessDeniedExceptionFactory(EntityDeleteAccessDeniedExceptionFactory $factory): void
    {
        $this->accessDeniedExceptionFactory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function assertDeleteGranted($entity): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function postFlush($entity, array $options): void
    {
    }

    final protected function createAccessDeniedException(string $reason = 'access denied'): AccessDeniedException
    {
        return $this->accessDeniedExceptionFactory->createAccessDeniedException($reason);
    }

    /**
     * @param object|string $entity
     *
     * @return ObjectManager
     */
    final protected function getEntityManager($entity): ObjectManager
    {
        $entityClass = \is_string($entity)
            ? ClassUtils::getRealClass($entity)
            : ClassUtils::getClass($entity);

        return $this->doctrine->getManagerForClass($entityClass);
    }

    final protected function getEntityRepository(string $entityClass): ObjectRepository
    {
        $entityClass = ClassUtils::getRealClass($entityClass);

        return $this->doctrine
            ->getManagerForClass($entityClass)
            ->getRepository($entityClass);
    }
}
