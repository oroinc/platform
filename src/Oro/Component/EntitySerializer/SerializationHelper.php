<?php

namespace Oro\Component\EntitySerializer;

/**
 * Provides a set of helper methods to serialize entity fields.
 */
class SerializationHelper
{
    /** @var DataTransformerInterface */
    private $dataTransformer;

    /**
     * @param DataTransformerInterface $dataTransformer
     */
    public function __construct(DataTransformerInterface $dataTransformer)
    {
        $this->dataTransformer = $dataTransformer;
    }

    /**
     * Prepares the given value for serialization.
     *
     * @param string           $entityClass
     * @param string           $fieldName
     * @param mixed            $fieldValue
     * @param array            $context
     * @param FieldConfig|null $fieldConfig
     *
     * @return mixed
     */
    public function transformValue(
        $entityClass,
        $fieldName,
        $fieldValue,
        array $context,
        FieldConfig $fieldConfig = null
    ) {
        return $this->dataTransformer->transform(
            $entityClass,
            $fieldName,
            $fieldValue,
            null !== $fieldConfig ? $fieldConfig->toArray(true) : [],
            $context
        );
    }

    /**
     * Passes a serialized item through the specified "post serialization" handler.
     *
     * @param array    $item
     * @param callable $handler
     * @param array    $context
     *
     * @return array
     */
    public function postSerialize(array $item, $handler, array $context)
    {
        return $handler($item, $context);
    }

    /**
     * Passes a collection of serialized items through the specified "post serialization" handler.
     *
     * @param array    $items
     * @param callable $handler
     * @param array    $context
     *
     * @return array
     */
    public function postSerializeCollection(array $items, $handler, array $context)
    {
        return $handler($items, $context);
    }

    /**
     * Passes a collection of serialized items through the specified "post serialization" handler
     * if the given config has this handler.
     *
     * @param array        $items
     * @param EntityConfig $config
     * @param array        $context
     *
     * @return array
     */
    public function processPostSerializeCollection(array $items, EntityConfig $config, array $context)
    {
        $collectionHandler = $config->getPostSerializeCollectionHandler();
        if (null !== $collectionHandler) {
            $items = $this->postSerializeCollection($items, $collectionHandler, $context);
        }

        return $items;
    }

    /**
     * Handles fields that are referenced to another child fields.
     * This method searches a value of the child field in the given serialized data,
     * adds the found value to the serialized data for the handling field
     * and returns the changed data.
     *
     * @param array        $serializedData
     * @param string       $entityClass
     * @param EntityConfig $entityConfig
     * @param array        $context
     * @param array        $fields [field name => [property, ...], ...]
     *
     * @return array
     */
    public function handleFieldsReferencedToChildFields(
        array $serializedData,
        $entityClass,
        EntityConfig $entityConfig,
        array $context,
        array $fields
    ) {
        foreach ($fields as $fieldName => $propertyPath) {
            $value = null;
            $firstField = $this->getFieldName($entityConfig, $propertyPath[0]);
            if (array_key_exists($firstField, $serializedData)) {
                $currentData = $serializedData[$firstField];
                $currentConfig = $this->getTargetEntityConfig($entityConfig, $firstField);
                if (null !== $currentConfig && is_array($currentData)) {
                    $lastIndex = count($propertyPath) - 1;
                    $index = 1;
                    while ($index < $lastIndex) {
                        $currentField = $this->getFieldName($currentConfig, $propertyPath[$index]);
                        if (!array_key_exists($currentField, $currentData)) {
                            break;
                        }
                        $currentData = $currentData[$currentField];
                        if (!is_array($currentData)) {
                            break;
                        }
                        $currentConfig = $this->getTargetEntityConfig($currentConfig, $currentField);
                        if (null === $currentConfig) {
                            break;
                        }
                        $index++;
                    }
                    if ($index === $lastIndex) {
                        $currentField = $this->getFieldName($currentConfig, $propertyPath[$lastIndex]);
                        if (array_key_exists($currentField, $currentData)) {
                            $value = $currentData[$currentField];
                            $currentConfig = $this->getTargetEntityConfig($currentConfig, $currentField);
                            if (null === $currentConfig) {
                                $value = $this->transformValue(
                                    $entityClass,
                                    $fieldName,
                                    $value,
                                    $context,
                                    $entityConfig->getField($fieldName)
                                );
                            }
                        }
                    }
                }
                $serializedData[$fieldName] = $value;
            }
        }

        return $serializedData;
    }

    /**
     * Attempts to get the configuration of a target entity of the specified field.
     *
     * @param EntityConfig $entityConfig
     * @param string       $fieldName
     *
     * @return EntityConfig|null
     */
    private function getTargetEntityConfig(EntityConfig $entityConfig, $fieldName)
    {
        $fieldConfig = $entityConfig->getField($fieldName);
        if (null === $fieldConfig) {
            return null;
        }

        return $fieldConfig->getTargetEntity();
    }

    /**
     * Gets the field name for the given entity property taking into account renaming.
     *
     * @param EntityConfig $entityConfig
     * @param string       $property
     *
     * @return string
     */
    private function getFieldName(EntityConfig $entityConfig, $property)
    {
        $renamedFields = $entityConfig->get(ConfigUtil::RENAMED_FIELDS);
        if (null !== $renamedFields && isset($renamedFields[$property])) {
            return $renamedFields[$property];
        }

        return $property;
    }
}
