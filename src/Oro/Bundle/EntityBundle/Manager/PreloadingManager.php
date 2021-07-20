<?php

namespace Oro\Bundle\EntityBundle\Manager;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityBundle\Event\PreloadEntityEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Responsible for preloading entities with required data.
 */
class PreloadingManager
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var EntityAliasResolver */
    private $entityAliasResolver;

    /** @var PropertyAccessorInterface */
    private $propertyAccessor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EventDispatcherInterface $eventDispatcher,
        EntityAliasResolver $entityAliasResolver,
        PropertyAccessorInterface $propertyAccessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->eventDispatcher = $eventDispatcher;
        $this->entityAliasResolver = $entityAliasResolver;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * Dispatches preload events with entities and fields to preload.
     *
     * @param array $entities
     * @param array $fieldsToPreload A tree of fields and subfields to preload in $entities, for example:
     *  [
     *      'product' => [
     *          'names' => [],
     *          'images' => ['image' => []],
     *      ],
     *  ]
     * @param array $context
     */
    public function preloadInEntities(array $entities, array $fieldsToPreload, array $context = []): void
    {
        if (!$entities) {
            return;
        }

        $firstEntity = reset($entities);
        $className = $this->doctrineHelper->getEntityClass($firstEntity);

        $this->preloadRecursively($className, $entities, $fieldsToPreload, $context);
    }

    /**
     * Recursively goes through $fieldsToPreload tree, dispatches preload events.
     */
    private function preloadRecursively(
        string $className,
        array $entities,
        array $fieldsToPreload,
        array $context
    ): void {
        /** @var ClassMetadata $entityMetadata */
        $entityMetadata = $this->doctrineHelper->getEntityMetadataForClass($className);
        foreach ($fieldsToPreload as $targetField => $subFields) {
            $this->assertAssociationExists($entityMetadata, $targetField, $className);
        }

        $eventName = sprintf(
            '%s.%s',
            PreloadEntityEvent::EVENT_NAME,
            $this->entityAliasResolver->getAlias($className)
        );
        $event = new PreloadEntityEvent($entities, $fieldsToPreload, $context);
        $this->eventDispatcher->dispatch($event, $eventName);

        foreach ($fieldsToPreload as $targetField => $subFields) {
            if (!$subFields) {
                // Skips $targetField as there are no fields to preload inside.
                continue;
            }

            $targetFieldEntities = $this->collectTargetEntities($entities, $entityMetadata, $targetField);
            if ($targetFieldEntities) {
                $targetFieldEntityClass = $entityMetadata->getAssociationTargetClass($targetField);
                $this->preloadRecursively(
                    $targetFieldEntityClass,
                    $targetFieldEntities,
                    $subFields,
                    $context
                );
            }
        }
    }

    /**
     * @param array $entities
     * @param ClassMetadata $entityMetadata
     * @param string $targetField
     * @return object[]
     */
    private function collectTargetEntities(array $entities, ClassMetadata $entityMetadata, string $targetField): array
    {
        $targetFieldEntities = [];
        $isToMany = $entityMetadata->getAssociationMapping($targetField)['type'] & ClassMetadata::TO_MANY;

        foreach ($entities as $entity) {
            $targetFieldValue = $this->propertyAccessor->getValue($entity, $targetField);
            if ($targetFieldValue && !$this->isCollectionNotInitialized($targetFieldValue)) {
                $targetFieldEntities[] = $isToMany ? $targetFieldValue->toArray() : $targetFieldValue;
            }
        }

        if ($isToMany && $targetFieldEntities) {
            $targetFieldEntities = array_merge(...$targetFieldEntities);
        }

        return $targetFieldEntities;
    }

    private function assertAssociationExists(
        ClassMetadata $entityMetadata,
        string $targetField,
        string $className
    ): void {
        if (!$entityMetadata->hasAssociation($targetField)) {
            throw new \LogicException(
                sprintf(
                    'Field %s of entity %s is not an association which can be preloaded',
                    $targetField,
                    $className
                )
            );
        }
    }

    private function isCollectionNotInitialized(?object $collection): bool
    {
        return $collection instanceof AbstractLazyCollection && !$collection->isInitialized();
    }
}
