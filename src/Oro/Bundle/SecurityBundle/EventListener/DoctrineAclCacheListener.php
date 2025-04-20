<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheProvider;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeInterface;
use Oro\Bundle\SecurityBundle\Owner\OwnerTreeProviderInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Util\ClassUtils;

/**
 * Clear Doctrine ACL query cache to be sure that queries will process hints
 * again with updated security information.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class DoctrineAclCacheListener
{
    private DoctrineAclCacheProvider $queryCacheProvider;
    private OwnerTreeProviderInterface $ownerTreeProvider;

    private bool $isCacheOutdated = false;
    private $ownerTree = null;

    /**
     * @var array [className => [fieldName => shouldValueBeCheckedOnBoolean, ...], ...]
     */
    private array $entitiesShouldBeProcessedByUpdate = [
        BusinessUnit::class => ['owner' => false]
    ];

    public function __construct(
        DoctrineAclCacheProvider $queryCacheProvider,
        OwnerTreeProviderInterface $ownerTreeProvider
    ) {
        $this->queryCacheProvider = $queryCacheProvider;
        $this->ownerTreeProvider = $ownerTreeProvider;
    }

    public function addEntityShouldBeProcessedByUpdate(string $entityClass, array $fieldNames): void
    {
        if (!\array_key_exists($entityClass, $this->entitiesShouldBeProcessedByUpdate)) {
            $this->entitiesShouldBeProcessedByUpdate[$entityClass] = [];
        }

        $this->entitiesShouldBeProcessedByUpdate[$entityClass] = array_merge(
            $this->entitiesShouldBeProcessedByUpdate[$entityClass],
            $fieldNames
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->isCacheOutdated) {
            return;
        }

        $em = $args->getEntityManager();
        $this->ownerTree = null;

        try {
            $changedEntities = $this->getChangedEntities($em->getUnitOfWork());
            $this->isCacheOutdated = count($changedEntities) > 0;

            if ($this->isCacheOutdated) {
                $this->queryCacheProvider->clearForEntities(User::class, $changedEntities);
            }
        } finally {
            $this->ownerTree = null;
        }
    }

    private function getChangedEntities(UnitOfWork $uow): array
    {
        $usersToBreakTheCache = [];
        $usersToBreakTheCache[] = $this->getUsersShouldBeUpdatedByUpdates($uow);
        $usersToBreakTheCache[] = $this->getUsersShouldBeUpdatedByDeletions($uow);
        $usersToBreakTheCache[] = $this->getUsersShouldBeUpdatedByCollectionUpdates($uow);

        return array_unique(array_merge(...$usersToBreakTheCache));
    }

    private function getUsersShouldBeUpdatedByDeletions(UnitOfWork $uow): array
    {
        $usersToBreakTheCache = [];
        $deletedBusinessUnits = $this->getInsertedOrDeletedEntities(
            $uow->getScheduledEntityDeletions(),
            [BusinessUnit::class]
        );
        foreach ($deletedBusinessUnits as $deletedEntity) {
            $ownerTree = $this->getOwnerTree();
            $parentBusinessUnits = [];
            $this->collectParentBUs($deletedEntity, $parentBusinessUnits);
            $buIds = array_merge(
                $parentBusinessUnits,
                [$deletedEntity->getId()],
                $ownerTree->getSubordinateBusinessUnitIds($deletedEntity->getId())
            );
            $usersToBreakTheCache[] = $ownerTree->getUsersAssignedToBusinessUnits($buIds);
        }

        return array_unique(array_merge(...$usersToBreakTheCache));
    }

    private function getUsersShouldBeUpdatedByUpdates(UnitOfWork $uow): array
    {
        $usersToBreakTheCache = [];
        $updatedEntities = $this->getUpdatedEntities($uow, $this->entitiesShouldBeProcessedByUpdate);
        foreach ($updatedEntities as $updatesEntityData) {
            [$entity, $fieldName, $changeSet] = $updatesEntityData;
            if ($entity instanceof BusinessUnit && 'owner' === $fieldName) {
                $oldParents = $newParents = [];
                if ($changeSet[0]) {
                    $this->collectParentBUs($changeSet[0], $oldParents);
                }
                if ($changeSet[1]) {
                    $this->collectParentBUs($changeSet[1], $newParents);
                }

                $buIds = array_unique(array_merge(
                    $oldParents,
                    $newParents,
                    [$entity->getId()]
                ));

                $usersToBreakTheCache[] = $this->getOwnerTree()->getUsersAssignedToBusinessUnits($buIds);
            } elseif ($entity instanceof Organization) {
                $userIds = [];
                foreach ($entity->getUsers() as $user) {
                    $userIds[] = $user->getId();
                }

                $usersToBreakTheCache[] = $userIds;
            }
        }

        return array_unique(array_merge(...$usersToBreakTheCache));
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getUsersShouldBeUpdatedByCollectionUpdates(UnitOfWork $uow): array
    {
        $usersToBreakTheCache = [];
        $updatedRelations = $this->getToManyRelations(
            $uow->getScheduledCollectionUpdates(),
            [
                User::class => ['userRoles', 'businessUnits', 'organizations'],
                BusinessUnit::class => ['users']
            ]
        );
        foreach ($updatedRelations as $updatesCollectionData) {
            /** @var $collection PersistentCollection */
            [$entity, $fieldName, $collection] = $updatesCollectionData;
            if ($entity instanceof User) {
                if ('userRoles' === $fieldName) {
                    $usersToBreakTheCache[] = [$entity->getId()];
                } elseif ('businessUnits' === $fieldName) {
                    $changedBusinessUnits = array_merge($collection->getInsertDiff(), $collection->getDeleteDiff());
                    $businessUnitsShouldBeUpdated = [];
                    foreach ($changedBusinessUnits as $changedBusinessUnit) {
                        $parents = [];
                        $this->collectParentBUs($changedBusinessUnit, $parents);
                        $businessUnitsShouldBeUpdated[] = $parents;
                    }
                    $businessUnitsShouldBeUpdated = array_unique(array_merge(...$businessUnitsShouldBeUpdated));
                    $users = $this->getOwnerTree()->getUsersAssignedToBusinessUnits(
                        $businessUnitsShouldBeUpdated
                    );
                    if ($entity->getId() && !\in_array($entity->getId(), $users, true)) {
                        $users[] = $entity->getId();
                    }
                    $usersToBreakTheCache[] = $users;
                } elseif ('organizations' === $fieldName) {
                    $changedOrganizations = array_merge($collection->getInsertDiff(), $collection->getDeleteDiff());
                    $businessUnitsShouldBeUpdated = [];
                    foreach ($entity->getBusinessUnits() as $businessUnit) {
                        if (\in_array($businessUnit->getOrganization(), $changedOrganizations, true)) {
                            $parents = [];
                            $this->collectParentBUs($businessUnit, $parents);
                            $businessUnitsShouldBeUpdated[] = $parents;
                        }
                    }
                    $businessUnitsShouldBeUpdated = array_unique(array_merge(...$businessUnitsShouldBeUpdated));
                    $users = $this->getOwnerTree()->getUsersAssignedToBusinessUnits(
                        $businessUnitsShouldBeUpdated
                    );
                    if ($entity->getId() && !\in_array($entity->getId(), $users, true)) {
                        $users[] = $entity->getId();
                    }
                    $usersToBreakTheCache[] = $users;
                }
            } elseif ($entity instanceof BusinessUnit) {
                if ('users' === $fieldName) {
                    $parentBuIds = [];
                    $this->collectParentBUs($entity, $parentBuIds);
                    $changedUserIds = [];
                    $changedUsers = array_merge($collection->getInsertDiff(), $collection->getDeleteDiff());
                    foreach ($changedUsers as $user) {
                        $changedUserIds[] = $user->getId();
                    }
                    $usersToBreakTheCache[] = array_unique(array_merge(
                        $changedUserIds,
                        $this->getOwnerTree()->getUsersAssignedToBusinessUnits(
                            $parentBuIds
                        )
                    ));
                }
            }
        }

        return array_unique(array_merge(...$usersToBreakTheCache));
    }

    private function getInsertedOrDeletedEntities(array $entities, array $supportedClasses): array
    {
        $changedEntities = [];
        foreach ($entities as $entity) {
            if (\in_array(ClassUtils::getRealClass($entity), $supportedClasses, true)) {
                $changedEntities[] = $entity;
            }
        }

        return $changedEntities;
    }

    private function getUpdatedEntities(UnitOfWork $uow, array $supportedClasses): array
    {
        $changedEntities = [];
        $entities = $uow->getScheduledEntityUpdates();
        foreach ($entities as $entity) {
            $entityClass = ClassUtils::getRealClass($entity);
            if (!\array_key_exists($entityClass, $supportedClasses)) {
                continue;
            }

            $fields = array_keys($supportedClasses[$entityClass]);
            $changeSet = $uow->getEntityChangeSet($entity);
            foreach ($fields as $fieldName) {
                if (\array_key_exists($fieldName, $changeSet)) {
                    if ($supportedClasses[$entityClass][$fieldName] === true
                        && (bool)$changeSet[$fieldName][0] === (bool)$changeSet[$fieldName][1]
                    ) {
                        continue;
                    }
                    $changedEntities[] = [$entity, $fieldName, $changeSet[$fieldName]];
                }
            }
        }

        return $changedEntities;
    }

    private function getToManyRelations(array $collections, array $supportedClasses): array
    {
        $changedEntities = [];
        /** @var PersistentCollection $collection */
        foreach ($collections as $collection) {
            $entity = $collection->getOwner();
            $entityClass = ClassUtils::getRealClass($entity);
            if (!\array_key_exists($entityClass, $supportedClasses)) {
                continue;
            }

            $associations = $supportedClasses[$entityClass];
            if ($associations) {
                $associationMapping = $collection->getMapping();
                if (\in_array($associationMapping['fieldName'], $associations, true)) {
                    $changedEntities[] = [$entity, $associationMapping['fieldName'], $collection];
                }
            }
        }

        return $changedEntities;
    }

    private function collectParentBUs(BusinessUnit $businessUnit, array &$parentBUs): void
    {
        $buId = $businessUnit->getId();
        if ($buId) {
            $parentBUs[] = $buId;
        }

        if ($businessUnit->getOwner()) {
            $this->collectParentBUs($businessUnit->getOwner(), $parentBUs);
        }
    }

    private function getOwnerTree(): OwnerTreeInterface
    {
        if (null === $this->ownerTree) {
            $this->ownerTree = $this->ownerTreeProvider->getTree();
        }

        return $this->ownerTree;
    }
}
