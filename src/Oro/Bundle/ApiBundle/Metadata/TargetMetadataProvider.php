<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessorInterface;

/**
 * A helper class to get metadata for concrete instance of an entity.
 */
class TargetMetadataProvider
{
    private ObjectAccessorInterface $objectAccessor;

    public function __construct(ObjectAccessorInterface $objectAccessor)
    {
        $this->objectAccessor = $objectAccessor;
    }

    public function getTargetMetadata(mixed $object, EntityMetadata $entityMetadata): EntityMetadata
    {
        $objectClassName = $this->objectAccessor->getClassName($object);
        if (!$objectClassName) {
            return $entityMetadata;
        }

        if ($this->hasIdentifierFieldsOnly($object, $entityMetadata)) {
            return $entityMetadata;
        }

        return $entityMetadata->getEntityMetadata($objectClassName) ?? $entityMetadata;
    }

    public function getAssociationTargetMetadata(
        mixed $object,
        AssociationMetadata $associationMetadata
    ): ?EntityMetadata {
        if (null === $object || is_scalar($object)) {
            return $associationMetadata->getTargetMetadata();
        }

        $objectClassName = $this->objectAccessor->getClassName($object);
        if (!$objectClassName) {
            return $associationMetadata->getTargetMetadata();
        }

        $targetMetadata = $associationMetadata->getTargetMetadata();
        if (null !== $targetMetadata && !$this->hasIdentifierFieldsOnly($object, $targetMetadata)) {
            $objectMetadata = $associationMetadata->getTargetMetadata($objectClassName);
            if (null !== $objectMetadata) {
                $targetMetadata = $objectMetadata;
            }
        }

        return $targetMetadata;
    }

    private function hasIdentifierFieldsOnly(mixed $object, EntityMetadata $entityMetadata): bool
    {
        $identifierFieldNames = $entityMetadata->getIdentifierFieldNames();
        $properties = $this->objectAccessor->toArray($object);
        if (\count($properties) !== \count($identifierFieldNames)) {
            return false;
        }

        foreach ($identifierFieldNames as $fieldName) {
            if (!\array_key_exists($fieldName, $properties)) {
                return false;
            }
        }

        return true;
    }
}
