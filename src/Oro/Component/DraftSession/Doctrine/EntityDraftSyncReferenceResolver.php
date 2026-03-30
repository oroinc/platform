<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Doctrine;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;

/**
 * Resolves Doctrine entity references for draft synchronization.
 *
 * Provides helper methods to get a managed reference to an entity
 * (either returning the entity itself if already managed, or obtaining a proxy reference),
 * and to resolve enum option references from either an object or a string identifier.
 */
class EntityDraftSyncReferenceResolver
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    /**
     * Returns a managed reference to the given entity.
     *
     * If the entity is already managed by the entity manager, it is returned as-is.
     * Otherwise, a Doctrine proxy reference is returned.
     * Returns null if the entity is null.
     */
    public function getReference(?object $entity): ?object
    {
        if (!$entity) {
            return null;
        }

        $entityClass = ClassUtils::getClass($entity);

        $entityManager = $this->doctrine->getManagerForClass($entityClass);
        if ($entityManager->contains($entity)) {
            return $entity;
        }

        $classMetadata = $entityManager->getClassMetadata($entityClass);
        $identifier = current($classMetadata->getIdentifierValues($entity));
        if (!$identifier) {
            return $entity;
        }

        return $entityManager->getReference($entityClass, $identifier);
    }

    /**
     * Returns a managed reference to an EnumOption.
     *
     * Accepts either an EnumOptionInterface object (resolved via {@see getReference()})
     * or a string identifier (resolved directly via Doctrine reference).
     */
    public function getEnumReference(object|string|null $enumOption): ?EnumOptionInterface
    {
        if (is_string($enumOption)) {
            return $this->doctrine->getManagerForClass(EnumOption::class)
                ->getReference(EnumOption::class, $enumOption);
        }

        return $this->getReference($enumOption);
    }
}
