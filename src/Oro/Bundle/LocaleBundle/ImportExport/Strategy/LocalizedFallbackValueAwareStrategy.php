<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\Strategy;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ConfigurableAddOrReplaceStrategy;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\ImportExport\Normalizer\LocalizationCodeFormatter;

class LocalizedFallbackValueAwareStrategy extends ConfigurableAddOrReplaceStrategy
{
    /** @var string */
    protected $localizedFallbackValueClass;

    /** @var \ReflectionProperty[] */
    protected $reflectionProperties = [];

    /**
     * @param string $localizedFallbackValueClass
     */
    public function setLocalizedFallbackValueClass($localizedFallbackValueClass)
    {
        $this->localizedFallbackValueClass = $localizedFallbackValueClass;
    }

    /** {@inheritdoc} */
    protected function beforeProcessEntity($entity)
    {
        $existingEntity = $this->findExistingEntity($entity);
        if (!$existingEntity) {
            return parent::beforeProcessEntity($entity);
        }

        $fields = $this->fieldHelper->getRelations($this->entityName);
        foreach ($fields as $field) {
            if ($this->isLocalizedFallbackValue($field)) {
                $fieldName = $field['name'];
                $this->mapCollections(
                    $this->fieldHelper->getObjectValue($entity, $fieldName),
                    $this->fieldHelper->getObjectValue($existingEntity, $fieldName)
                );
            }
        }

        return parent::beforeProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function afterProcessEntity($entity)
    {
        $metadata = $this->doctrineHelper->getEntityMetadata($this->localizedFallbackValueClass);
        $localizedValueRelations = [];
        foreach ($metadata->getAssociationMappings() as $name => $mapping) {
            if ($metadata->isAssociationInverseSide($name) && $metadata->isCollectionValuedAssociation($name)) {
                $localizedValueRelations[] = $name;
            }
        }

        $entityFields = $this->fieldHelper->getRelations($this->entityName);
        foreach ($entityFields as $field) {
            if ($this->isLocalizedFallbackValue($field)) {
                $this->removeNotInitializedEntities($entity, $field, $localizedValueRelations);
                $this->setLocalizationKeys($entity, $field);
            }
        }

        return parent::afterProcessEntity($entity);
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
        if (ClassUtils::getClass($entity) === $this->localizedFallbackValueClass) {
            $isFullData = true;
        }

        return parent::processEntity($entity, $isFullData, $isPersistNew, $itemData, $searchContext, $entityIsRelation);
    }

    /**
     * {@inheritdoc}
     */
    protected function importExistingEntity($entity, $existingEntity, $itemData = null, array $excludedFields = [])
    {
        if (ClassUtils::getClass($entity) === $this->localizedFallbackValueClass) {
            $metadata = $this->doctrineHelper->getEntityMetadata($this->localizedFallbackValueClass);
            foreach ($metadata->getAssociationMappings() as $name => $mapping) {
                if ($metadata->isAssociationInverseSide($name) && $metadata->isCollectionValuedAssociation($name)) {
                    // exclude all *-to-many relations from import
                    $excludedFields[] = $name;
                }
            }
        }

        parent::importExistingEntity($entity, $existingEntity, $itemData, $excludedFields);
    }

    /**
     * @param string $field
     * @return bool
     */
    protected function isLocalizedFallbackValue($field)
    {
        return $this->fieldHelper->isRelation($field)
            && is_a($field['related_entity_name'], $this->localizedFallbackValueClass, true);
    }

    /**
     * Clear not initialized entities that might remain in localized entity because of recursive relations
     *
     * @param $entity
     * @param array $field
     * @param array $relations
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
     * @param object $entity
     * @param Collection $collection
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
     * @param object $entity
     * @param array $field
     * @throws \Exception
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
     * @param string $fieldName
     * @return \ReflectionProperty
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
     * @param Collection $importedCollection
     * @param Collection $sourceCollection
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

    /**
     * {@inheritdoc}
     *
     * No need to search LocalizedFallbackValue by identity fields, consider entities without ids as new
     */
    protected function findEntityByIdentityValues($entityName, array $identityValues)
    {
        if (is_a($entityName, $this->localizedFallbackValueClass, true)) {
            return null;
        }

        return parent::findEntityByIdentityValues($entityName, $identityValues);
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
}
