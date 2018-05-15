<?php

namespace Oro\Bundle\SearchBundle\Engine;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Mode;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Mapping index data from entities' data - common code.
 *
 * @package Oro\Bundle\SearchBundle\Engine
 */
abstract class AbstractMapper
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var array
     * @deprecated since 1.8 Please use mappingProvider for mapping config
     */
    protected $mappingConfig;

    /**
     * @var SearchMappingProvider
     */
    protected $mappingProvider;

    /**
     * @var PropertyAccessorInterface
     */
    protected $propertyAccessor;

    /**
     * @var HtmlTagHelper
     */
    protected $htmlTagHelper;

    /**
     * @param SearchMappingProvider $mappingProvider
     */
    public function setMappingProvider(SearchMappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param PropertyAccessorInterface $propertyAccessor
     */
    public function setPropertyAccessor(PropertyAccessorInterface $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param HtmlTagHelper $htmlTagHelper
     */
    public function setHtmlTagHelper(HtmlTagHelper $htmlTagHelper)
    {
        $this->htmlTagHelper = $htmlTagHelper;
    }

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
        if (is_object($objectOrArray)) {
            $getter = sprintf('get%s', $fieldName);
            if (method_exists($objectOrArray, $getter)) {
                return $objectOrArray->$getter();
            }
        }

        try {
            return $this->propertyAccessor->getValue($objectOrArray, $fieldName);
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
        return $this->mappingProvider->getEntityMapParameter($entity, $parameter, $defaultValue);
    }

    /**
     * Get mapping config for entity
     *
     * @param string $entity
     *
     * @return array
     */
    public function getEntityConfig($entity)
    {
        return $this->mappingProvider->getEntityConfig($entity);
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
        $value  = Mode::NORMAL;

        if ($config) {
            $value = $config['mode'];
        }

        return $value;
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

                $objectData[$fieldConfig['target_type']][Indexer::TEXT_ALL_DATA_FIELD] = $this->buildAllDataField(
                    $textAllDataField,
                    $value
                );

                $objectData[$fieldConfig['target_type']] = array_map('trim', $objectData[$fieldConfig['target_type']]);
            }
        }

        return $objectData;
    }

    /**
     * @param string $fieldName
     * @param mixed $value
     * @return string
     */
    abstract protected function clearTextValue($fieldName, $value);

    /**
     * @param string $original
     * @param string $addition
     * @return string
     */
    public function buildAllDataField($original, $addition)
    {
        $addition = $this->clearTextValue(Indexer::TEXT_ALL_DATA_FIELD, $addition);
        $clearedAddition = Query::clearString($addition);

        $original .= sprintf(' %s %s ', $addition, $clearedAddition);
        $original = implode(
            Query::DELIMITER,
            array_unique(
                explode(
                    Query::DELIMITER,
                    $original
                )
            )
        );

        return $original;
    }
}
