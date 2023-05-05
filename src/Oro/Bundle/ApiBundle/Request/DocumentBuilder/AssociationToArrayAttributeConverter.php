<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\TargetMetadataProvider;

/**
 * Provides methods to build array representation of associations.
 */
class AssociationToArrayAttributeConverter
{
    private ObjectAccessorInterface $objectAccessor;
    private TargetMetadataProvider $targetMetadataProvider;

    public function __construct(
        ObjectAccessorInterface $objectAccessor,
        TargetMetadataProvider $targetMetadataProvider
    ) {
        $this->objectAccessor = $objectAccessor;
        $this->targetMetadataProvider = $targetMetadataProvider;
    }

    /**
     * @param mixed                    $object
     * @param AssociationMetadata|null $association
     *
     * @return mixed The result can be an array, a scalar or NULL
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function convertObjectToArray(mixed $object, AssociationMetadata $association = null): mixed
    {
        if (null === $object || is_scalar($object)) {
            return $object;
        }

        if (null === $association) {
            $result = $this->objectAccessor->toArray($object);
        } else {
            $metadata = $this->targetMetadataProvider->getAssociationTargetMetadata($object, $association);
            if (null === $metadata) {
                $result = $this->objectAccessor->toArray($object);
            } else {
                $data = $this->objectAccessor->toArray($object);
                if ($metadata->hasIdentifierFieldsOnly()) {
                    $idFieldNames = $metadata->getIdentifierFieldNames();
                    if (1 === \count($idFieldNames)) {
                        $fieldName = reset($idFieldNames);
                        $result = $data[$fieldName] ?? null;
                    } else {
                        $result = [];
                        foreach ($idFieldNames as $fieldName) {
                            if (\array_key_exists($fieldName, $data)) {
                                $result[$fieldName] = $data[$fieldName];
                            }
                        }
                    }
                } else {
                    $result = [];
                    $this->addMeta($result, $data, $metadata);
                    $this->addAttributes($result, $data, $metadata);
                    $this->addRelationships($result, $data, $metadata);
                    if ($association->isCollapsed() && 1 === \count($result)) {
                        $result = reset($result);
                    }
                }
            }
        }

        return $result;
    }

    public function convertCollectionToArray(iterable $collection, AssociationMetadata $association = null): array
    {
        $result = [];
        foreach ($collection as $object) {
            $result[] = $this->convertObjectToArray($object, $association);
        }

        return $result;
    }

    private function addMeta(array &$result, array $data, EntityMetadata $metadata): void
    {
        $properties = $metadata->getMetaProperties();
        foreach ($properties as $name => $property) {
            $resultName = $property->getResultName();
            if (\array_key_exists($name, $data)) {
                $result[$resultName] = $data[$name];
            }
        }
    }

    private function addAttributes(array &$result, array $data, EntityMetadata $metadata): void
    {
        $fields = $metadata->getFields();
        foreach ($fields as $name => $field) {
            $result[$name] = $data[$name] ?? null;
        }
    }

    private function addRelationships(array &$result, array $data, EntityMetadata $metadata): void
    {
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $association) {
            $value = null;
            $isCollection = $association->isCollection();
            if (\array_key_exists($name, $data)) {
                $val = $data[$name];
                if (!$this->isEmptyRelationship($val, $isCollection)) {
                    $value = $isCollection
                        ? $this->convertCollectionToArray($val, $association)
                        : $this->convertObjectToArray($val, $association);
                }
            }
            if (null === $value && $isCollection) {
                $value = [];
            }
            $result[$name] = $value;
        }
    }

    private function isEmptyRelationship(mixed $value, bool $isCollection): bool
    {
        return $isCollection
            ? empty($value)
            : null === $value;
    }
}
