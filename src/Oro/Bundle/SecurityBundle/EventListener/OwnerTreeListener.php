<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Clears the owner tree and doctrine query caches.
 */
class OwnerTreeListener
{
    private OwnerTreeProviderInterface $ownerTreeProvider;
    /** @var array [class name => [[field name, ...], [association name, ...]], ...] */
    private array $securityClasses = [];
    private bool $isCacheOutdated = false;

    public function __construct(OwnerTreeProviderInterface $ownerTreeProvider)
    {
        $this->ownerTreeProvider = $ownerTreeProvider;
    }

    /**
     * @param string   $class        The FQCN of an entity to be monitored
     * @param string[] $fields       The names of fields or to-one associations
     * @param string[] $associations The names of to-many associations
     */
    public function addSupportedClass(string $class, array $fields = [], array $associations = []): void
    {
        $this->securityClasses[$class] = [$fields, $associations];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isCacheOutdated || !$this->securityClasses) {
            return;
        }

        $uow = $args->getEntityManager()->getUnitOfWork();
        $this->isCacheOutdated =
            $this->checkInsertedOrDeletedEntities($uow->getScheduledEntityInsertions())
            || $this->checkInsertedOrDeletedEntities($uow->getScheduledEntityDeletions())
            || $this->checkUpdatedEntities($uow)
            || $this->checkToManyRelations($uow->getScheduledCollectionUpdates())
            || $this->checkToManyRelations($uow->getScheduledCollectionDeletions());
    }

    public function postFlush(): void
    {
        if ($this->isCacheOutdated) {
            $this->ownerTreeProvider->clearCache();
            $this->isCacheOutdated = false;
        }
    }

    private function checkInsertedOrDeletedEntities(array $entities): bool
    {
        foreach ($entities as $entity) {
            if (isset($this->securityClasses[ClassUtils::getRealClass($entity)])) {
                return true;
            }
        }

        return false;
    }

    private function checkUpdatedEntities(UnitOfWork $uow): bool
    {
        $entities = $uow->getScheduledEntityUpdates();
        foreach ($entities as $entity) {
            $entityClass = ClassUtils::getRealClass($entity);
            if (!isset($this->securityClasses[$entityClass])) {
                continue;
            }

            [$fields] = $this->securityClasses[$entityClass];
            if ($fields) {
                $changeSet = $uow->getEntityChangeSet($entity);
                if (array_intersect(array_keys($changeSet), $fields)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkToManyRelations(array $collections): bool
    {
        /** @var PersistentCollection $collection */
        foreach ($collections as $collection) {
            $entityClass = ClassUtils::getRealClass($collection->getOwner());
            if (!isset($this->securityClasses[$entityClass])) {
                continue;
            }

            [, $associations] = $this->securityClasses[$entityClass];
            if ($associations) {
                $associationMapping = $collection->getMapping();
                if (\in_array($associationMapping['fieldName'], $associations, true)) {
                    return true;
                }
            }
        }

        return false;
    }
}
