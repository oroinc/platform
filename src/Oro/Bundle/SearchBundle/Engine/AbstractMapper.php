<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\SearchBundle\Query\Query;

abstract class AbstractMapper
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var array
     */
    protected $mappingConfig;

    /**
     * Get object field value
     *
     * @param object|array $objectOrArray
     * @param string       $fieldName
     *
     * @return mixed
     */
    public function getFieldValue($objectOrArray, $fieldName)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        try {
            return $propertyAccessor->getValue($objectOrArray, $fieldName);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get mapping parameter for entity
     *
     * @param string $entity
     * @param string $parameter
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function getEntityMapParameter($entity, $parameter, $defaultValue = false)
    {
        $entityConfig = $this->getEntityConfig($entity);

        if ($entityConfig && isset($entityConfig[$parameter])) {
            return $entityConfig[$parameter];
        }

        return $defaultValue;
    }

    /**
     * Get mapping config for entity
     *
     * @param string $entity
     *
     * @return bool|array
     */
    public function getEntityConfig($entity)
    {
        if (isset($this->mappingConfig[(string)$entity])) {
            return $this->mappingConfig[(string)$entity];
        }

        return false;
    }

    /**
     * Returns mode attribute from entity mapping config
     *
     * @param string $entity
     *
     * @return bool|string
     */
    public function getEntityModeConfig($entity)
    {
        $config = $this->getEntityConfig($entity);
        $value  = false;

        if (false !== $config) {
            $value = $config['mode'];
        }

        return $value;
    }

    /**
     * Set related fields values
     *
     * @param string $alias
     * @param array  $objectData
     * @param array  $relationFields
     * @param object $relationObject
     * @param string $parentName
     * @param bool   $isArray
     *
     * @return array
     */
    protected function setRelatedFields(
        $alias,
        $objectData,
        $relationFields,
        $relationObject,
        $parentName,
        $isArray = false
    ) {
        foreach ($relationFields as $relationObjectField) {
            $value = $this->getFieldValue($relationObject, $relationObjectField['name']);
            if ($value) {
                $relationObjectField['name'] = $parentName;
                $objectData = $this->setDataValue(
                    $alias,
                    $objectData,
                    $relationObjectField,
                    $value,
                    $isArray
                );
            }
        }

        return $objectData;
    }

    /**
     * Set value for meta fields by type
     *
     * @param string $alias
     * @param array  $objectData
     * @param array  $fieldConfig
     * @param mixed  $value
     * @param bool   $isArray
     *
     * @return array
     */
    protected function setDataValue($alias, $objectData, $fieldConfig, $value, $isArray = false)
    {
        if ($value) {
            //check if field have target_fields parameter
            $targetFields = isset($fieldConfig['target_fields'])
                ? $fieldConfig['target_fields']
                : [$fieldConfig['name']];

            if ($fieldConfig['target_type'] != Query::TYPE_TEXT) {
                foreach ($targetFields as $targetField) {
                    if ($isArray) {
                        $objectData[$fieldConfig['target_type']][$targetField][] = $value;
                    } else {
                        $objectData[$fieldConfig['target_type']][$targetField] = $value;
                    }

                }
            } else {
                foreach ($targetFields as $targetField) {
                    if (!isset($objectData[$fieldConfig['target_type']][$targetField])) {
                        $objectData[$fieldConfig['target_type']][$targetField] = '';
                    }

                    $objectData[$fieldConfig['target_type']][$targetField] .= sprintf(' %s ', $value);
                }

                $textAllDataField = '';
                if (isset($objectData[$fieldConfig['target_type']][Indexer::TEXT_ALL_DATA_FIELD])) {
                    $textAllDataField = $objectData[$fieldConfig['target_type']][Indexer::TEXT_ALL_DATA_FIELD];
                }

                $clearedValue = Query::clearString($value);
                $textAllDataField .= sprintf(' %s %s ', $value, $clearedValue);

                $objectData[$fieldConfig['target_type']][Indexer::TEXT_ALL_DATA_FIELD] = implode(
                    Query::DELIMITER,
                    array_unique(
                        explode(
                            Query::DELIMITER,
                            $textAllDataField
                        )
                    )
                );

                $objectData[$fieldConfig['target_type']] = array_map('trim', $objectData[$fieldConfig['target_type']]);
            }
        }

        return $objectData;
    }
}
