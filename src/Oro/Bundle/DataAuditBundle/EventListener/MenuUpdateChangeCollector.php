<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\Model\MenuAuditFieldName;
use Oro\Bundle\DataAuditBundle\Service\AuditEntryRecorder;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdateInterface;

/**
 * Collects what a flush changes in the menu updates
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class MenuUpdateChangeCollector
{
    private const array IGNORED_ASSOCIATIONS = ['scope'];

    private array $collected = [];

    /** @var array<string, array<string, bool>>|null [related class => [property => is a collection]] */
    private ?array $watchedAssociations = null;

    public function __construct(
        private readonly AuditEntryRecorder $auditEntryRecorder,
        private readonly string $menuUpdateClass
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $uow = $entityManager->getUnitOfWork();
        $menuUpdates = $uow->getIdentityMap()[$this->menuUpdateClass] ?? [];

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($this->isMenuUpdate($entity)) {
                $this->collect($entity, Audit::ACTION_CREATE, $uow->getEntityChangeSet($entity));
            }
        }
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($this->isMenuUpdate($entity)) {
                $this->collect($entity, Audit::ACTION_UPDATE, $uow->getEntityChangeSet($entity));
            } elseif ($entity instanceof AbstractLocalizedFallbackValue) {
                $this->collectEditedLocalizedValue($entity, $uow, $menuUpdates);
            } else {
                $this->collectEditedRelatedEntity($entity, $entityManager, $menuUpdates);
            }
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($this->isMenuUpdate($entity)) {
                $this->collect($entity, Audit::ACTION_REMOVE, $this->getStateBeforeRemoval($entity, $entityManager));
            } elseif (!$entity instanceof AbstractLocalizedFallbackValue) {
                $this->collectRemovedRelatedEntity($entity, $entityManager, $menuUpdates);
            }
        }
        foreach ($uow->getScheduledCollectionUpdates() as $collection) {
            $this->collectChangedCollection($collection, $entityManager);
        }
    }

    public function release(): array
    {
        $collected = $this->collected;
        $this->collected = [];

        return $collected;
    }

    private function collect(MenuUpdateInterface $menuUpdate, string $action, array $changeSet): void
    {
        $scope = $menuUpdate->getScope();
        if (null === $scope || !$this->auditEntryRecorder->isEnabled()) {
            return;
        }

        $groupKey = $menuUpdate->getMenu() . '|' . spl_object_id($scope);
        $this->collected[$groupKey]['menu'] = (string)$menuUpdate->getMenu();
        $this->collected[$groupKey]['scope'] = $scope;

        $itemKey = (string)$menuUpdate->getKey();
        $item = $this->collected[$groupKey]['items'][$itemKey] ?? null;
        $this->collected[$groupKey]['items'][$itemKey] = [
            'menuUpdate' => $menuUpdate,
            'parentKey' => (string)$menuUpdate->getParentKey(),
            'action' => $item['action'] ?? $action,
            'changeSet' => array_merge($item['changeSet'] ?? [], $changeSet),
        ];
    }

    private function collectChangedCollection(
        PersistentCollection $collection,
        EntityManagerInterface $entityManager
    ): void {
        $owner = $collection->getOwner();
        if (!$this->isMenuUpdate($owner)) {
            return;
        }

        $mapping = $collection->getMapping();
        $field = (string)$mapping['fieldName'];
        if (is_a((string)$mapping['targetEntity'], AbstractLocalizedFallbackValue::class, true)) {
            $this->collectReplacedLocalizedValues($owner, $field, $collection);

            return;
        }

        $this->collect($owner, Audit::ACTION_UPDATE, [
            $field => [
                $this->getItemsBeforeChanges($collection->getSnapshot(), $entityManager),
                $collection->toArray(),
            ],
        ]);
    }

    private function collectReplacedLocalizedValues(
        MenuUpdateInterface $menuUpdate,
        string $field,
        PersistentCollection $collection
    ): void {
        $values = [];
        foreach ($collection->getDeleteDiff() as $value) {
            if ($value instanceof AbstractLocalizedFallbackValue) {
                $values[$this->getLocalizationName($value)]['old'] = $this->getText($value);
            }
        }
        foreach ($collection->getInsertDiff() as $value) {
            if ($value instanceof AbstractLocalizedFallbackValue) {
                $values[$this->getLocalizationName($value)]['new'] = $this->getText($value);
            }
        }

        $this->collectLocalizedChanges($menuUpdate, $field, $values);
    }

    private function collectEditedLocalizedValue(
        AbstractLocalizedFallbackValue $value,
        UnitOfWork $uow,
        array $menuUpdates
    ): void {
        $changeSet = $uow->getEntityChangeSet($value);
        $text = $changeSet['string'] ?? $changeSet['text'] ?? null;
        if (null === $text) {
            return;
        }

        foreach ($menuUpdates as $menuUpdate) {
            $collections = ['titles' => $menuUpdate->getTitles(), 'descriptions' => $menuUpdate->getDescriptions()];
            foreach ($collections as $field => $collection) {
                if (!$this->isLoaded($collection) || !$collection->contains($value)) {
                    continue;
                }

                $this->collectLocalizedChanges(
                    $menuUpdate,
                    $field,
                    [$this->getLocalizationName($value) => ['old' => $text[0], 'new' => $text[1]]]
                );

                return;
            }
        }
    }

    /**
     * The state a removed menu update was in, by the properties it is mapped with: next to them the unit of
     * work keeps the foreign key columns of the same relations, which name the database and not the menu.
     *
     * @return array<string, array{mixed, null}>
     */
    private function getStateBeforeRemoval(object $entity, EntityManagerInterface $entityManager): array
    {
        $metadata = $entityManager->getClassMetadata($entity::class);

        $state = [];
        foreach ($entityManager->getUnitOfWork()->getOriginalEntityData($entity) as $property => $value) {
            if ($metadata->hasField($property) || $metadata->hasAssociation($property)) {
                $state[$property] = [$value, null];
            }
        }

        return $state;
    }

    private function collectEditedRelatedEntity(
        object $entity,
        EntityManagerInterface $entityManager,
        array $menuUpdates
    ): void {
        $owner = $this->findOwner($entity, $entityManager, $menuUpdates);
        if (null === $owner) {
            return;
        }

        [$menuUpdate, $field, $value] = $owner;
        if ($value instanceof Collection) {
            $items = $value->toArray();
            $change = [$this->getItemsBeforeChanges($items, $entityManager), $items];
        } else {
            $change = [$this->getEntityBeforeChanges($entity, $entityManager), $this->getNewValue($entity)];
        }

        $this->collect($menuUpdate, Audit::ACTION_UPDATE, [$field => $change]);
    }

    private function collectRemovedRelatedEntity(
        object $entity,
        EntityManagerInterface $entityManager,
        array $menuUpdates
    ): void {
        $owner = $this->findOwner($entity, $entityManager, $menuUpdates);
        if (null === $owner) {
            return;
        }

        [$menuUpdate, $field, $value] = $owner;
        if (!$value instanceof Collection) {
            $this->collect($menuUpdate, Audit::ACTION_UPDATE, [$field => [$entity, null]]);
        }
    }

    private function findOwner(object $entity, EntityManagerInterface $entityManager, array $menuUpdates): ?array
    {
        if (!$menuUpdates) {
            return null;
        }

        $fields = $this->getWatchedAssociations($entityManager)[$this->getEntityClass($entity, $entityManager)] ?? [];
        if (!$fields) {
            return null;
        }

        $metadata = $entityManager->getClassMetadata($this->menuUpdateClass);
        foreach ($menuUpdates as $menuUpdate) {
            foreach ($fields as $field => $isCollection) {
                $value = $metadata->getFieldValue($menuUpdate, $field);
                $isOwner = $isCollection
                    ? $this->isLoaded($value) && $value->contains($entity)
                    : $value === $entity;

                if ($isOwner) {
                    return [$menuUpdate, $field, $value];
                }
            }
        }

        return null;
    }

    private function getWatchedAssociations(EntityManagerInterface $entityManager): array
    {
        if (null === $this->watchedAssociations) {
            $this->watchedAssociations = [];
            $metadata = $entityManager->getClassMetadata($this->menuUpdateClass);
            foreach ($metadata->getAssociationNames() as $field) {
                if (\in_array($field, self::IGNORED_ASSOCIATIONS, true)) {
                    continue;
                }

                $this->watchedAssociations[$metadata->getAssociationTargetClass($field)][$field] =
                    $metadata->isCollectionValuedAssociation($field);
            }
        }

        return $this->watchedAssociations;
    }

    private function getItemsBeforeChanges(array $items, EntityManagerInterface $entityManager): array
    {
        return array_map(
            fn (mixed $item): mixed => \is_object($item)
                ? $this->getEntityBeforeChanges($item, $entityManager)
                : $item,
            $items
        );
    }

    private function getEntityBeforeChanges(object $entity, EntityManagerInterface $entityManager): object
    {
        $changeSet = $entityManager->getUnitOfWork()->getEntityChangeSet($entity);
        if (!$changeSet) {
            return $entity;
        }

        $metadata = $entityManager->getClassMetadata($entity::class);
        $before = clone $entity;
        foreach ($changeSet as $field => [$old]) {
            if ($metadata->hasField($field) || $metadata->hasAssociation($field)) {
                $metadata->setFieldValue($before, $field, $old);
            }
        }

        return $before;
    }

    private function getNewValue(object $entity): ?object
    {
        return $entity instanceof File && $entity->isEmptyFile() ? null : $entity;
    }

    private function collectLocalizedChanges(MenuUpdateInterface $menuUpdate, string $field, array $values): void
    {
        $changeSet = [];
        foreach ($values as $localization => $value) {
            $name = (string)new MenuAuditFieldName($field, (string)$localization);
            $changeSet[$name] = [$value['old'] ?? null, $value['new'] ?? null];
        }

        if ($changeSet) {
            $this->collect($menuUpdate, Audit::ACTION_UPDATE, $changeSet);
        }
    }

    private function getText(AbstractLocalizedFallbackValue $value): ?string
    {
        return $value->getString() ?? $value->getText();
    }

    private function getLocalizationName(AbstractLocalizedFallbackValue $value): string
    {
        return (string)$value->getLocalization()?->getName();
    }

    private function getEntityClass(object $entity, EntityManagerInterface $entityManager): string
    {
        return $entityManager->getClassMetadata($entity::class)->getName();
    }

    private function isLoaded(mixed $collection): bool
    {
        return $collection instanceof Collection
            && (!$collection instanceof PersistentCollection || $collection->isInitialized());
    }

    private function isMenuUpdate(mixed $entity): bool
    {
        return $entity instanceof MenuUpdateInterface && is_a($entity, $this->menuUpdateClass);
    }
}
