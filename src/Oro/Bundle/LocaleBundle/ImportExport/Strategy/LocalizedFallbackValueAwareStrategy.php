<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\Strategy;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

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
        $fields = $this->fieldHelper->getRelations($this->entityName);
        foreach ($fields as $field) {
            if ($this->isLocalizedFallbackValue($field)) {
                $this->setLocalizationKeys($entity, $field);
            }
        }

        return parent::afterProcessEntity($entity);
    }

    /**
     * @param $field
     * @return bool
     */
    protected function isLocalizedFallbackValue($field)
    {
        return $this->fieldHelper->isRelation($field)
            && is_a($field['related_entity_name'], $this->localizedFallbackValueClass, true);
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
}
