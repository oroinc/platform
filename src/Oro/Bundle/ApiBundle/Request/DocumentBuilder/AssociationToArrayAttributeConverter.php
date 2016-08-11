<?php

namespace Oro\Bundle\ApiBundle\Request\DocumentBuilder;

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
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    public function convertObjectToArray($object, EntityMetadata $metadata = null)
    {
        if (null === $object || is_scalar($object)) {
            return $object;
        }

        if (null === $metadata) {
            $result = $this->objectAccessor->toArray($object);
        } else {
            $data = $this->objectAccessor->toArray($object);
            if ($metadata->hasIdentifierFieldsOnly()) {
                $result = count($data) === 1
                    ? reset($data)
                    : $data;
            } else {
                $result = [];
                $this->addMeta($result, $data, $metadata);
                $this->addAttributes($result, $data, $metadata);
                $this->addRelationships($result, $data, $metadata);
            }
        }

        return $result;
    }

    /**
     * @param array|\Traversable  $collection
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    public function convertCollectionToArray($collection, EntityMetadata $metadata = null)
    {
        $result = [];
        foreach ($collection as $object) {
            $result[] = $this->convertObjectToArray($object, $metadata);
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
            if (array_key_exists($name, $data)) {
                $result[$name] = $data[$name];
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
                        ? $this->convertCollectionToArray($val, $association->getTargetMetadata())
                        : $this->convertObjectToArray($val, $association->getTargetMetadata());
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
