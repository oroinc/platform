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

    /** @var \ReflectionProperty[] */
    protected $reflectionProperties = [];

    /** @var array */
    private $localizedValueRelations = [];

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
        return parent::beforeProcessEntity($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function afterProcessEntity($entity)
    {
        $entityFields = $this->fieldHelper->getRelations($this->entityName);
        foreach ($entityFields as $field) {
            if ($this->isLocalizedFallbackValue($field)) {
                $localizedValueRelations = $this->getLocalizedFallbackValueRelations($field['related_entity_name']);
                $this->removeNotInitializedEntities($entity, $field, $localizedValueRelations);
                $this->setLocalizationKeys($entity, $field);
            }
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param string $className
     *
     * @return array
     */
    private function getLocalizedFallbackValueRelations(string $className): array
    {
        if (!array_key_exists($className, $this->localizedValueRelations)) {
            $metadata = $this->doctrineHelper->getEntityMetadata($className);
            foreach ($metadata->getAssociationMappings() as $name => $mapping) {
                if ($metadata->isAssociationInverseSide($name) && $metadata->isCollectionValuedAssociation($name)) {
                    $this->localizedValueRelations[$className][] = $name;
                }
            }
        }

        return $this->localizedValueRelations[$className] ?? [];
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

    /**
     * @param object $entity
     *
     * @return bool
     */
    private function isLocalizedFallbackValueEntity(object $entity): bool
    {
        return is_a($this->doctrineHelper->getClass($entity), $this->localizedFallbackValueClass, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function importExistingEntity($entity, $existingEntity, $itemData = null, array $excludedFields = [])
    {
        if ($this->isLocalizedFallbackValueEntity($entity)) {
            $metadata = $this->doctrineHelper->getEntityMetadata($entity);
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
                $localizationCode = LocalizationCodeFormatter::formatKey($sourceValue->getLocalization());
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
            $localizationCode = LocalizationCodeFormatter::formatKey($entity->getLocalization());
            if (array_key_exists($localizationCode, $searchContext)) {
                $identifier = $this->databaseHelper->getIdentifier($entity);
                $existingEntity = $searchContext[$localizationCode];
                if ($existingEntity && !$identifier) {
                    $identifier = $this->databaseHelper->getIdentifier($existingEntity);
                    $identifierName = $this->databaseHelper->getIdentifierFieldName($entity);
                    $this->fieldHelper->setObjectValue($entity, $identifierName, $identifier);
                }

                return $existingEntity ? $entity : null;
            }
        }

        return parent::findExistingEntity($entity, $searchContext);
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
