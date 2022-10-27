<?php

namespace Oro\Bundle\LocaleBundle\ImportExport\Strategy;

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
}
