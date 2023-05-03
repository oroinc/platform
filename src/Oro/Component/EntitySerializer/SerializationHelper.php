<?php

namespace Oro\Component\EntitySerializer;

/**
 * Provides a set of helper methods to serialize entity fields.
 */
class SerializationHelper
{
    private DataTransformerInterface $dataTransformer;

    public function __construct(DataTransformerInterface $dataTransformer)
    {
        $this->dataTransformer = $dataTransformer;
    }

    /**
     * Prepares the given value for serialization.
     */
    public function transformValue(mixed $fieldValue, array $context, ?FieldConfig $fieldConfig): mixed
    {
        return $this->dataTransformer->transform(
            $fieldValue,
            null !== $fieldConfig ? $fieldConfig->toArray(true) : [],
            $context
        );
    }

    /**
     * Passes a serialized items through "post serialization" handler for a single item
     * if the given config has this handler.
     */
    public function postSerializeItem(?array $item, EntityConfig $config, array $context): ?array
    {
        if (!$item) {
            return $item;
        }

        $handler = $config->getPostSerializeHandler();
        if (null !== $handler) {
            $item = $handler($item, $context);
        }

        return $item;
    }

    /**
     * Passes a serialized items through "post serialization" handler for a list of items
     * if the given config has this handler.
     */
    public function postSerializeCollection(array $items, EntityConfig $config, array $context): array
    {
        if (!$items) {
            return $items;
        }

        $collectionHandler = $config->getPostSerializeCollectionHandler();
        if (null !== $collectionHandler) {
            $items = $collectionHandler($items, $context);
        }

        return $items;
    }

    /**
     * Passes a serialized items through "post serialization" handlers
     * for a single item and for a list of items if the given config has these handlers.
     */
    public function processPostSerializeItems(array $items, EntityConfig $config, array $context): array
    {
        if (empty($items)) {
            return $items;
        }

        $handler = $config->getPostSerializeHandler();
        if (null !== $handler) {
            foreach ($items as $key => $item) {
                if (\is_array($item) && !empty($item)) {
                    $item = $handler($item, $context);
                }
                $items[$key] = $item;
            }
        }
        $collectionHandler = $config->getPostSerializeCollectionHandler();
        if (null !== $collectionHandler) {
            $items = $collectionHandler($items, $context);
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
     * @param EntityConfig $entityConfig
     * @param array        $context
     * @param array        $fields [field name => [property, ...], ...]
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function handleFieldsReferencedToChildFields(
        array $serializedData,
        EntityConfig $entityConfig,
        array $context,
        array $fields
    ): array {
        foreach ($fields as $fieldName => $propertyPath) {
            $value = null;
            $firstField = $this->getFieldName($entityConfig, $propertyPath[0]);
            if (\array_key_exists($firstField, $serializedData)) {
                $currentData = $serializedData[$firstField];
                $currentConfig = $this->getTargetEntityConfig($entityConfig, $firstField);
                if (null !== $currentConfig && \is_array($currentData)) {
                    $lastIndex = \count($propertyPath) - 1;
                    $index = 1;
                    while ($index < $lastIndex) {
                        $currentField = $this->getFieldName($currentConfig, $propertyPath[$index]);
                        if (!\array_key_exists($currentField, $currentData)) {
                            break;
                        }
                        $currentData = $currentData[$currentField];
                        if (!\is_array($currentData)) {
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
                        if (\array_key_exists($currentField, $currentData)) {
                            $value = $currentData[$currentField];
                            $currentConfig = $this->getTargetEntityConfig($currentConfig, $currentField);
                            if (null === $currentConfig) {
                                $value = $this->transformValue(
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
     */
    private function getTargetEntityConfig(EntityConfig $entityConfig, string $fieldName): ?EntityConfig
    {
        $fieldConfig = $entityConfig->getField($fieldName);
        if (null === $fieldConfig) {
            return null;
        }

        return $fieldConfig->getTargetEntity();
    }

    /**
     * Gets the field name for the given entity property taking into account renaming.
     */
    private function getFieldName(EntityConfig $entityConfig, string $property): string
    {
        $renamedFields = $entityConfig->get(ConfigUtil::RENAMED_FIELDS);
        if (null !== $renamedFields && isset($renamedFields[$property])) {
            return $renamedFields[$property];
        }

        return $property;
    }
}
