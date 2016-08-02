<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessor;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessorInterface;

abstract class AbstractDocumentBuilder implements DocumentBuilderInterface
{
    const DATA   = 'data';
    const ERRORS = 'errors';

    /** @var ObjectAccessorInterface */
    protected $objectAccessor;

    /** @var array */
    protected $result = [];

    public function __construct()
    {
        $this->objectAccessor = new ObjectAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function getDocument()
    {
        return $this->result;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->result = [];
    }

    /**
     * {@inheritdoc}
     */
    public function setDataObject($object, EntityMetadata $metadata = null)
    {
        $this->assertNoData();

        $this->result[self::DATA] = null;
        if (null !== $object) {
            $this->result[self::DATA] = $this->convertObjectToArray($object, $metadata);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDataCollection($collection, EntityMetadata $metadata = null)
    {
        $this->assertNoData();

        $this->result[self::DATA] = [];
        if (is_array($collection) || $collection instanceof \Traversable) {
            $this->result[self::DATA] = $this->convertCollectionToArray($collection, $metadata);
        } else {
            throw $this->createUnexpectedValueException('array or \Traversable', $collection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorObject(Error $error)
    {
        $this->assertNoData();

        $this->result[self::ERRORS] = [$this->convertErrorToArray($error)];
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorCollection(array $errors)
    {
        $this->assertNoData();

        $errorsData = [];
        foreach ($errors as $error) {
            $errorsData[] = $this->convertErrorToArray($error);
        }
        $this->result[self::ERRORS] = $errorsData;
    }

    /**
     * @param array|\Traversable  $collection
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function convertCollectionToArray($collection, EntityMetadata $metadata = null)
    {
        $result = [];
        foreach ($collection as $object) {
            $result[] = $this->convertObjectToArray($object, $metadata);
        }

        return $result;
    }

    /**
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    abstract protected function convertObjectToArray($object, EntityMetadata $metadata = null);

    /**
     * @param Error $error
     *
     * @return array
     */
    abstract protected function convertErrorToArray(Error $error);

    /**
     * @param string $entityClass
     * @param bool   $throwException
     *
     * @return string|null
     */
    abstract protected function convertToEntityType($entityClass, $throwException = true);

    /**
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     *
     * @return string
     */
    protected function getEntityTypeForObject($object, EntityMetadata $metadata)
    {
        $className = $this->objectAccessor->getClassName($object);

        return $className
            ? $this->getEntityType($className, $metadata->getClassName())
            : $this->getEntityType($metadata->getClassName());
    }

    /**
     * @param string      $entityClass
     * @param string|null $fallbackEntityClass
     *
     * @return string
     */
    protected function getEntityType($entityClass, $fallbackEntityClass = null)
    {
        if (null === $fallbackEntityClass) {
            $entityType = $this->convertToEntityType($entityClass);
        } else {
            $entityType = $this->convertToEntityType($entityClass, false);
            if (!$entityType) {
                $entityType = $this->convertToEntityType($fallbackEntityClass);
            }
        }

        return $entityType;
    }

    /**
     * Checks whether a given metadata contains only identifier fields(s).
     *
     * @param EntityMetadata $metadata
     *
     * @return bool
     */
    protected function isIdentity(EntityMetadata $metadata)
    {
        if (count($metadata->getAssociations()) > 0) {
            return false;
        }

        $idFields = $metadata->getIdentifierFieldNames();
        $fields = $metadata->getFields();

        return
            count($fields) === count($idFields)
            && count(array_diff_key($fields, array_flip($idFields))) === 0;
    }

    /**
     * Checks that the primary data does not exist.
     */
    protected function assertNoData()
    {
        if (array_key_exists(self::DATA, $this->result)) {
            throw new \InvalidArgumentException('A primary data already exist.');
        }
    }

    /**
     * @param string $expectedType
     * @param mixed  $value
     *
     * @return \UnexpectedValueException
     */
    protected function createUnexpectedValueException($expectedType, $value)
    {
        return new \UnexpectedValueException(
            sprintf(
                'Expected argument of type "%s", "%s" given.',
                $expectedType,
                is_object($value) ? get_class($value) : gettype($value)
            )
        );
    }
}
