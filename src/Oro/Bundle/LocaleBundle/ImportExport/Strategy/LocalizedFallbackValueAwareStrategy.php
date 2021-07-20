<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\Strategy;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;

/**
 * Import strategy for entities which have relations to LocalizedFallbackValue collections.
 */
class LocalizedFallbackValueAwareStrategy extends ConfigurableAddOrReplaceStrategy
{
    /** @var string */
    protected $localizedFallbackValueClass;

    /**
     * @var \ReflectionProperty[]
     * @deprecated Error keys logic moved to \Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent
     * @internal
     */
    protected $reflectionProperties = [];

    /**
     * @param string $localizedFallbackValueClass
     */
    public function setLocalizedFallbackValueClass($localizedFallbackValueClass)
    {
        $this->localizedFallbackValueClass = $localizedFallbackValueClass;
    }

    /**
     * {@inheritdoc}
     */
    protected function processEntity(
        $entity,
        $isFullData = false,
        $isPersistNew = false,
        $itemData = null,
        array $searchContext = [],
        $entityIsRelation = false
    ) {
        if ($this->isLocalizedFallbackValueEntity($entity)) {
            $isFullData = true;
        }

        return parent::processEntity($entity, $isFullData, $isPersistNew, $itemData, $searchContext, $entityIsRelation);
    }

    private function isLocalizedFallbackValueEntity(object $entity): bool
    {
        return is_a($entity, $this->localizedFallbackValueClass, true);
    }

    /**
     * {@inheritdoc}
     *
     * Adds extra functionality to the base method:
     * - loads existing collection or LocalizedFallbackValue entities if any
     */
    protected function generateSearchContextForRelationsUpdate($entity, $entityName, $fieldName, $isPersistRelation)
    {
        $fields = $this->fieldHelper->getRelations($entityName);
        $existingEntity = $this->findExistingEntity($entity);
        if ($existingEntity && $this->isLocalizedFallbackValue($fields[$fieldName])) {
            $searchContext = [];
            $sourceCollection = $this->fieldHelper->getObjectValue($existingEntity, $fieldName);
            /** @var LocalizedFallbackValue $sourceValue */
            foreach ($sourceCollection as $sourceValue) {
                $localizationCode = LocalizationCodeFormatter::formatName($sourceValue->getLocalization());
                $searchContext[$localizationCode] = $sourceValue;
            }

            return $searchContext;
        }

        return parent::generateSearchContextForRelationsUpdate($entity, $entityName, $fieldName, $isPersistRelation);
    }

    /**
     * {@inheritdoc}
     */
    protected function findExistingEntity($entity, array $searchContext = [])
    {
        if ($this->isLocalizedFallbackValueEntity($entity)) {
            $localizationCode = LocalizationCodeFormatter::formatName($entity->getLocalization());

            return $searchContext[$localizationCode] ?? null;
        }

        return parent::findExistingEntity($entity, $searchContext);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function isLocalizedFallbackValue($field)
    {
        return is_array($field) &&
            $this->fieldHelper->isRelation($field) &&
            is_a($field['related_entity_name'], $this->localizedFallbackValueClass, true);
    }

    /**
     * {@inheritdoc}
     *
     * No need to search LocalizedFallbackValue by identity fields in new entities storage
     */
    protected function combineIdentityValues($entity, $entityClass, array $searchContext)
    {
        if (is_a($entityClass, $this->localizedFallbackValueClass, true)) {
            return null;
        }

        return parent::combineIdentityValues($entity, $entityClass, $searchContext);
    }

    /**
     * @deprecated Error keys logic moved to \Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent
     * @internal
     */
    protected function removeNotInitializedEntities($entity, array $field, array $relations)
    {
        /** @var Collection|LocalizedFallbackValue[] $localizedFallbackValues */
        $localizedFallbackValues = $this->fieldHelper->getObjectValue($entity, $field['name']);
        foreach ($localizedFallbackValues as $value) {
            // check all inverse relations
            foreach ($relations as $relation) {
                /** @var Collection $collection */
                $collection = $this->fieldHelper->getObjectValue($value, $relation);
                if ($collection) {
                    $this->removedDetachedEntities($entity, $collection);
                }
            }
        }
    }

    /**
     * @deprecated Error keys logic moved to \Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent
     * @internal
     */
    protected function removedDetachedEntities($entity, Collection $collection)
    {
        foreach ($collection as $key => $element) {
            $uow = $this->doctrineHelper->getEntityManager($element)->getUnitOfWork();
            // remove foreign and detached entities
            if (spl_object_hash($entity) !== spl_object_hash($element) &&
                $uow->getEntityState($element, UnitOfWork::STATE_DETACHED) === UnitOfWork::STATE_DETACHED
            ) {
                $collection->remove($key);
            }
        }
    }

    /**
     * @deprecated Error keys logic moved to \Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent
     * @internal
     */
    protected function setLocalizationKeys($entity, array $field)
    {
        /** @var Collection|LocalizedFallbackValue[] $localizedFallbackValues */
        $localizedFallbackValues = $this->fieldHelper->getObjectValue($entity, $field['name']);

        $newLocalizedFallbackValues = new ArrayCollection();
        foreach ($localizedFallbackValues as $localizedFallbackValue) {
            $key = LocalizationCodeFormatter::formatName($localizedFallbackValue->getLocalization());
            $newLocalizedFallbackValues->set($key, $localizedFallbackValue);
        }

        // Reflection usage to full replace collections
        $this->getReflectionProperty($field['name'])->setValue($entity, $newLocalizedFallbackValues);
    }

    /**
     * @deprecated Error keys logic moved to \Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent
     * @internal
     */
    protected function getReflectionProperty($fieldName)
    {
        if (array_key_exists($fieldName, $this->reflectionProperties)) {
            return $this->reflectionProperties[$fieldName];
        }

        $this->reflectionProperties[$fieldName] = new \ReflectionProperty($this->entityName, $fieldName);
        $this->reflectionProperties[$fieldName]->setAccessible(true);

        return $this->reflectionProperties[$fieldName];
    }

    /**
     * @deprecated Error keys logic moved to \Oro\Bundle\ImportExportBundle\Event\StrategyValidationEvent
     * @internal
     */
    protected function mapCollections(Collection $importedCollection, Collection $sourceCollection)
    {
        if ($importedCollection->isEmpty()) {
            return;
        }

        if ($sourceCollection->isEmpty()) {
            return;
        }

        $sourceCollectionArray = $sourceCollection->toArray();

        /** @var LocalizedFallbackValue $sourceValue */
        foreach ($sourceCollectionArray as $sourceValue) {
            $key = LocalizationCodeFormatter::formatKey($sourceValue->getLocalization());
            $sourceCollectionArray[$key] = $sourceValue->getId();
        }

        $importedCollection
            ->map(
                function (LocalizedFallbackValue $importedValue) use ($sourceCollectionArray) {
                    $key = LocalizationCodeFormatter::formatKey($importedValue->getLocalization());
                    if (array_key_exists($key, $sourceCollectionArray)) {
                        $this->fieldHelper->setObjectValue($importedValue, 'id', $sourceCollectionArray[$key]);
                    }
                }
            );
    }
}
