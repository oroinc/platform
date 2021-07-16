<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessorInterface;

/**
 * A helper class to get metadata for concrete instance of an entity.
 */
class TargetMetadataProvider
{
    /** @var ObjectAccessorInterface */
    private $objectAccessor;

    public function __construct(ObjectAccessorInterface $objectAccessor)
    {
        $this->objectAccessor = $objectAccessor;
    }

    /**
     * @param mixed          $object
     * @param EntityMetadata $entityMetadata
     *
     * @return EntityMetadata
     */
    public function getTargetMetadata($object, EntityMetadata $entityMetadata): EntityMetadata
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

    /**
     * @param mixed               $object
     * @param AssociationMetadata $associationMetadata
     *
     * @return EntityMetadata|null
     */
    public function getAssociationTargetMetadata($object, AssociationMetadata $associationMetadata): ?EntityMetadata
    {
        if (null === $object || \is_scalar($object)) {
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

    /**
     * @param mixed          $object
     * @param EntityMetadata $entityMetadata
     *
     * @return bool
     */
    private function hasIdentifierFieldsOnly($object, EntityMetadata $entityMetadata): bool
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
