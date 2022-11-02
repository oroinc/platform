<?php

namespace Oro\Bundle\EntityBundle\Handler;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * The base class for classes that implements of a business logic responsible to delete an entity.
 */
abstract class AbstractEntityDeleteHandler implements EntityDeleteHandlerInterface
{
    protected const ENTITY = 'entity';

    /** @var EntityDeleteHandlerExtensionRegistry */
    private $extensionRegistry;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var EntityDeleteAccessDeniedExceptionFactory */
    private $accessDeniedExceptionFactory;

    public function setExtensionRegistry(EntityDeleteHandlerExtensionRegistry $extensionRegistry): void
    {
        $this->extensionRegistry = $extensionRegistry;
    }

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
    public function isDeleteGranted($entity): bool
    {
        try {
            $this->assertDeleteGranted($entity);
        } catch (AccessDeniedException $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, bool $flush = true, array $options = []): ?array
    {
        $this->assertDeleteGranted($entity);
        $this->deleteWithoutFlush($entity, $options);

        $flushOptions = $options;
        $flushOptions[self::ENTITY] = $entity;
        if ($flush) {
            $this->flush($flushOptions);

            return null;
        }

        return $flushOptions;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $options): void
    {
        $entity = $options[self::ENTITY];
        $this->getEntityManager($entity)->flush();
        $this->postFlush($entity, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll(array $listOfOptions): void
    {
        $flushedEntityManagers = [];
        foreach ($listOfOptions as $options) {
            $entity = $options[self::ENTITY];
            $em = $this->getEntityManager($entity);
            $emHash = spl_object_hash($em);
            if (!isset($flushedEntityManagers[$emHash])) {
                $em->flush();
                $flushedEntityManagers[$emHash] = true;
            }
            $this->postFlush($entity, $options);
        }
    }

    /**
     * Deletes the given entity but does not flush it to the database.
     *
     * @param object $entity  The entity to be deleted
     * @param array  $options The options are passed to delete() method
     */
    protected function deleteWithoutFlush($entity, array $options): void
    {
        $this->getEntityManager($entity)->remove($entity);
    }

    /**
     * Checks if a delete operation is granted.
     *
     * @param object $entity
     *
     * @throws AccessDeniedException if the delete operation is forbidden
     */
    final protected function assertDeleteGranted($entity): void
    {
        $this->getHandlerExtension($entity)->assertDeleteGranted($entity);
    }

    /**
     * Preforms additional operations after the entity was deleted and flushed to the database.
     *
     * @param object $entity  The entity to be deleted
     * @param array  $options The options are returned by delete() method
     */
    final protected function postFlush($entity, array $options): void
    {
        $this->getHandlerExtension($entity)->postFlush($entity, $options);
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

    /**
     * @param object $entity
     *
     * @return EntityDeleteHandlerExtensionInterface
     */
    private function getHandlerExtension($entity): EntityDeleteHandlerExtensionInterface
    {
        return $this->extensionRegistry->getHandlerExtension(ClassUtils::getClass($entity));
    }
}
