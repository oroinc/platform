<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;

class AssociationToArrayAttributeConverter
{
    /** @var ObjectAccessorInterface */
    protected $objectAccessor;

    /**
     * @param ObjectAccessorInterface $objectAccessor
     */
    public function __construct(ObjectAccessorInterface $objectAccessor)
    {
        $this->objectAccessor = $objectAccessor;
    }

    /**
     * @param mixed                    $object
     * @param AssociationMetadata|null $association
     *
     * @return array
     */
    public function convertObjectToArray($object, AssociationMetadata $association = null)
    {
        if (null === $object || is_scalar($object)) {
            return $object;
        }

        if (null === $association) {
            $result = $this->objectAccessor->toArray($object);
        } else {
            $metadata = $association->getTargetMetadata();
            if (null === $metadata) {
                $result = $this->objectAccessor->toArray($object);
            } else {
                $data = $this->objectAccessor->toArray($object);
                if ($metadata->hasIdentifierFieldsOnly()) {
                    $idFieldNames = $metadata->getIdentifierFieldNames();
                    if (1 === count($idFieldNames)) {
                        $fieldName = reset($idFieldNames);
                        $result = array_key_exists($fieldName, $data)
                            ? $data[$fieldName]
                            : null;
                    } else {
                        $result = [];
                        foreach ($idFieldNames as $fieldName) {
                            if (array_key_exists($fieldName, $data)) {
                                $result[$fieldName] = $data[$fieldName];
                            }
                        }
                    }
                } else {
                    $result = [];
                    $this->addMeta($result, $data, $metadata);
                    $this->addAttributes($result, $data, $metadata);
                    $this->addRelationships($result, $data, $metadata);
                    if ($association->isCollapsed()) {
                        $count = count($result);
                        if (1 === $count) {
                            $result = reset($result);
                        } elseif (1 === $count) {
                            $result = null;
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param array|\Traversable       $collection
     * @param AssociationMetadata|null $association
     *
     * @return array
     */
    public function convertCollectionToArray($collection, AssociationMetadata $association = null)
    {
        $result = [];
        foreach ($collection as $object) {
            $result[] = $this->convertObjectToArray($object, $association);
        }

        return $result;
    }

    /**
     * @param array          $result
     * @param array          $data
     * @param EntityMetadata $metadata
     */
    protected function addMeta(array &$result, array $data, EntityMetadata $metadata)
    {
        $properties = $metadata->getMetaProperties();
        foreach ($properties as $name => $property) {
            $resultName = $property->getResultName();
            if (array_key_exists($name, $data)) {
                $result[$resultName] = $data[$name];
            }
        }
    }

    /**
     * @param array          $result
     * @param array          $data
     * @param EntityMetadata $metadata
     */
    protected function addAttributes(array &$result, array $data, EntityMetadata $metadata)
    {
        $fields = $metadata->getFields();
        foreach ($fields as $name => $field) {
            $result[$name] = array_key_exists($name, $data)
                ? $data[$name]
                : null;
        }
    }

    /**
     * @param array          $result
     * @param array          $data
     * @param EntityMetadata $metadata
     */
    protected function addRelationships(array &$result, array $data, EntityMetadata $metadata)
    {
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $association) {
            $value = null;
            $isCollection = $association->isCollection();
            if (array_key_exists($name, $data)) {
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

    /**
     * @param mixed $value
     * @param bool  $isCollection
     *
     * @return bool
     */
    protected function isEmptyRelationship($value, $isCollection)
    {
        return $isCollection
            ? empty($value)
            : null === $value;
    }
}
