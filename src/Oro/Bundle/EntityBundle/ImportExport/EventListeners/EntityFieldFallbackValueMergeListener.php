<?php

namespace Oro\Bundle\EntityBundle\ImportExport\EventListeners;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValueRepository;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;

/**
 * This class is responsible for merging entity`s related new EntityFieldFallbackValue`s
 * with same existing EntityFieldFallbackValue`s
 * to prevent their duplication and/or id number growth
 */
class EntityFieldFallbackValueMergeListener
{
    /** @var FieldHelper */
    private $fieldHelper;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    public function __construct(FieldHelper $fieldHelper, DoctrineHelper $doctrineHelper)
    {
        $this->fieldHelper = $fieldHelper;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function onProcessAfter(StrategyEvent $event)
    {
        $entity = $event->getEntity();
        if ($this->doctrineHelper->isNewEntity($entity)) {
            return;
        }
        $fieldNames = $this->getFallbackRelatedFieldNames($entity);
        $newFallbackValues = $this->getNewFallbackValues($fieldNames, $entity);
        if (!$newFallbackValues) {
            return;
        }

        $em = $this->doctrineHelper->getEntityManagerForClass(EntityFieldFallbackValue::class);
        /** @var EntityFieldFallbackValueRepository $repo */
        $repo = $em->getRepository(EntityFieldFallbackValue::class);

        $existingFallbackValues = $repo->findByEntityFields($entity, array_keys($newFallbackValues));
        foreach ($existingFallbackValues as $fieldName => $existingValue) {
            if (!array_key_exists($fieldName, $newFallbackValues)) {
                continue;
            }
            $newValue = $newFallbackValues[$fieldName];
            $existingValue->setScalarValue($newValue->getScalarValue());
            $existingValue->setArrayValue($newValue->getArrayValue());
            $existingValue->setFallback($newValue->getFallback());
            $this->fieldHelper->setObjectValue($entity, $fieldName, $existingValue);
            $em->detach($newValue);
        }
    }

    /**
     * @param object $entity
     * @return string[]
     */
    private function getFallbackRelatedFieldNames($entity): array
    {
        $relations = $this->fieldHelper->getRelations($this->doctrineHelper->getEntityClass($entity));
        $names = [];
        foreach ($relations as $relation) {
            if (is_a($relation['related_entity_name'], EntityFieldFallbackValue::class, true)) {
                $names[] = $relation['name'];
            }
        }
        return $names;
    }

    /**
     * @param array $fieldNames
     * @param object $entity
     * @return EntityFieldFallbackValue[] array like ['fieldName' => EntityFieldFallbackValue]
     */
    private function getNewFallbackValues(array $fieldNames, $entity): array
    {
        $newEntities = [];
        foreach ($fieldNames as $fieldName) {
            $value = $this->fieldHelper->getObjectValue($entity, $fieldName);
            if ($value instanceof EntityFieldFallbackValue && $this->doctrineHelper->isNewEntity($value)) {
                $newEntities[$fieldName] = $value;
            }
        }
        return $newEntities;
    }
}
