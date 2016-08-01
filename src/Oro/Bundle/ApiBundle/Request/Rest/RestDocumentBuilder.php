<?php

namespace Oro\Bundle\ApiBundle\Request\Rest;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class RestDocumentBuilder extends AbstractDocumentBuilder
{
    const OBJECT_TYPE = 'entity';

    const ERROR_CODE   = 'code';
    const ERROR_TITLE  = 'title';
    const ERROR_DETAIL = 'detail';
    const ERROR_SOURCE = 'source';

    /**
     * {@inheritdoc}
     */
    public function getDocument()
    {
        $result = null;
        if (array_key_exists(self::DATA, $this->result)) {
            $result = $this->result[self::DATA];
        } elseif (array_key_exists(self::ERRORS, $this->result)) {
            $result = $this->result[self::ERRORS];
        }
        if (null === $result) {
            $result = [];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertCollectionToArray($collection, EntityMetadata $metadata = null)
    {
        $result = [];
        foreach ($collection as $object) {
            $result[] = null === $object || is_scalar($object)
                ? $object
                : $this->convertObjectToArray($object, $metadata);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertObjectToArray($object, EntityMetadata $metadata = null)
    {
        if (null === $metadata) {
            $result = $this->objectAccessor->toArray($object);
            if (!array_key_exists(self::OBJECT_TYPE, $result)) {
                $objectClass = $this->objectAccessor->getClassName($object);
                if ($objectClass) {
                    $result[self::OBJECT_TYPE] = $objectClass;
                }
            }
        } else {
            $result = [];
            $data = $this->objectAccessor->toArray($object);
            if ($metadata->hasMetaProperty(ConfigUtil::CLASS_NAME)) {
                $result[self::OBJECT_TYPE] = $this->getEntityTypeForObject($object, $metadata);
            }
            $this->addMeta($result, $data, $metadata);
            $this->addAttributes($result, $data, $metadata);
            $this->addRelationships($result, $data, $metadata);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertErrorToArray(Error $error)
    {
        $result = [];

        if ($error->getCode()) {
            $result[self::ERROR_CODE] = (string)$error->getCode();
        }
        if ($error->getTitle()) {
            $result[self::ERROR_TITLE] = $error->getTitle();
        }
        if ($error->getDetail()) {
            $result[self::ERROR_DETAIL] = $error->getDetail();
        }
        $source = $error->getSource();
        if ($source) {
            if ($source->getPointer()) {
                $result[self::ERROR_SOURCE] = $source->getPointer();
            } elseif ($source->getParameter()) {
                $result[self::ERROR_SOURCE] = $source->getParameter();
            } elseif ($source->getPropertyPath()) {
                $result[self::ERROR_SOURCE] = $source->getPropertyPath();
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertToEntityType($entityClass, $throwException = true)
    {
        return $entityClass;
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
            if ($association->isCollection()) {
                $value = [];
                if (array_key_exists($name, $data)) {
                    $val = $data[$name];
                    if (!empty($val)) {
                        foreach ($val as $object) {
                            $value[] = $this->processRelatedObject($object, $association);
                        }
                    }
                }
            } else {
                $value = null;
                if (array_key_exists($name, $data)) {
                    $val = $data[$name];
                    if (null !== $val) {
                        $value = $this->processRelatedObject($val, $association);
                    }
                }
            }
            $result[$name] = $value;
        }
    }

    /**
     * @param mixed               $object
     * @param AssociationMetadata $associationMetadata
     *
     * @return mixed
     */
    protected function processRelatedObject($object, AssociationMetadata $associationMetadata)
    {
        if (is_scalar($object)) {
            return $object;
        }

        $targetMetadata = $associationMetadata->getTargetMetadata();
        if ($targetMetadata && $this->isIdentity($targetMetadata)) {
            $data = $this->objectAccessor->toArray($object);

            return count($data) === 1
                ? reset($data)
                : $data;
        }

        return $this->convertObjectToArray($object, $targetMetadata);
    }

    /**
     * {@inheritdoc}
     */
    protected function isIdentity(EntityMetadata $metadata)
    {
        if (count($metadata->getMetaProperties()) > 0) {
            return false;
        }

        return parent::isIdentity($metadata);
    }
}
