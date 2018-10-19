<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\AssociationToArrayAttributeConverter;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessor;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\ObjectAccessorInterface;

/**
 * The base class for document builders for different types of Data API responses.
 */
abstract class AbstractDocumentBuilder implements DocumentBuilderInterface
{
    public const DATA   = 'data';
    public const ERRORS = 'errors';

    /** @var ObjectAccessorInterface */
    protected $objectAccessor;

    /** @var array */
    protected $result = [];

    /** @var AssociationToArrayAttributeConverter */
    private $arrayAttributeConverter;

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
    public function setDataObject($object, RequestType $requestType, EntityMetadata $metadata = null)
    {
        $this->assertNoData();

        $this->result[self::DATA] = null;
        if (null !== $object) {
            $this->result[self::DATA] = $this->convertObjectToArray($object, $requestType, $metadata);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDataCollection($collection, RequestType $requestType, EntityMetadata $metadata = null)
    {
        $this->assertNoData();

        $this->result[self::DATA] = [];
        if (is_array($collection) || $collection instanceof \Traversable) {
            $this->result[self::DATA] = $this->convertCollectionToArray($collection, $requestType, $metadata);
        } else {
            throw $this->createUnexpectedValueException('array or \Traversable', $collection);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addIncludedObject($object, RequestType $requestType, EntityMetadata $metadata = null)
    {
        if (!array_key_exists(self::DATA, $this->result)) {
            throw new \InvalidArgumentException('A primary data should be set.');
        }

        if (null !== $object) {
            $this->addRelatedObject($this->convertObjectToArray($object, $requestType, $metadata));
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
     * @param RequestType         $requestType
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function convertCollectionToArray(
        $collection,
        RequestType $requestType,
        EntityMetadata $metadata = null
    ) {
        $result = [];
        foreach ($collection as $object) {
            $result[] = $this->convertObjectToArray($object, $requestType, $metadata);
        }

        return $result;
    }

    /**
     * @param mixed               $object
     * @param RequestType         $requestType
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    abstract protected function convertObjectToArray(
        $object,
        RequestType $requestType,
        EntityMetadata $metadata = null
    );

    /**
     * @param Error $error
     *
     * @return array
     */
    abstract protected function convertErrorToArray(Error $error);

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     * @param bool        $throwException
     *
     * @return string|null
     */
    abstract protected function convertToEntityType($entityClass, RequestType $requestType, $throwException = true);

    /**
     * @param mixed          $object
     * @param RequestType    $requestType
     * @param EntityMetadata $metadata
     *
     * @return string
     */
    protected function getEntityTypeForObject($object, RequestType $requestType, EntityMetadata $metadata)
    {
        $className = $this->objectAccessor->getClassName($object);

        return $className
            ? $this->getEntityType($className, $requestType, $metadata->getClassName())
            : $this->getEntityType($metadata->getClassName(), $requestType);
    }

    /**
     * @param string      $entityClass
     * @param RequestType $requestType
     * @param string|null $fallbackEntityClass
     *
     * @return string
     */
    protected function getEntityType($entityClass, RequestType $requestType, $fallbackEntityClass = null)
    {
        if (null === $fallbackEntityClass) {
            $entityType = $this->convertToEntityType($entityClass, $requestType);
        } else {
            $entityType = $this->convertToEntityType($entityClass, $requestType, false);
            if (!$entityType) {
                $entityType = $this->convertToEntityType($fallbackEntityClass, $requestType);
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
    protected function hasIdentifierFieldsOnly(EntityMetadata $metadata)
    {
        return $metadata->hasIdentifierFieldsOnly();
    }

    /**
     * @return AssociationToArrayAttributeConverter
     */
    protected function getArrayAttributeConverter()
    {
        if (null === $this->arrayAttributeConverter) {
            $this->arrayAttributeConverter = $this->createArrayAttributeConverter();
        }

        return $this->arrayAttributeConverter;
    }

    /**
     * @return AssociationToArrayAttributeConverter
     */
    protected function createArrayAttributeConverter()
    {
        return new AssociationToArrayAttributeConverter($this->objectAccessor);
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

    /**
     * @param array               $data
     * @param RequestType         $requestType
     * @param string              $associationName
     * @param AssociationMetadata $association
     *
     * @return mixed
     */
    protected function getRelationshipValue(
        array $data,
        RequestType $requestType,
        $associationName,
        AssociationMetadata $association
    ) {
        $result = null;
        $isCollection = $association->isCollection();
        if (array_key_exists($associationName, $data)) {
            $val = $data[$associationName];
            if (!$this->isEmptyRelationship($val, $isCollection)) {
                if (DataType::isAssociationAsField($association->getDataType())) {
                    $result = $isCollection
                        ? $this->getArrayAttributeConverter()->convertCollectionToArray($val, $association)
                        : $this->getArrayAttributeConverter()->convertObjectToArray($val, $association);
                } else {
                    $result = $isCollection
                        ? $this->processRelatedCollection($val, $requestType, $association)
                        : $this->processRelatedObject($val, $requestType, $association);
                }
            }
        }
        if (null === $result && $isCollection) {
            $result = [];
        }

        return $result;
    }

    /**
     * @param array|\Traversable  $collection
     * @param RequestType         $requestType
     * @param AssociationMetadata $associationMetadata
     *
     * @return array
     */
    protected function processRelatedCollection(
        $collection,
        RequestType $requestType,
        AssociationMetadata $associationMetadata
    ) {
        $result = [];
        foreach ($collection as $object) {
            $result[] = $this->processRelatedObject($object, $requestType, $associationMetadata);
        }

        return $result;
    }

    /**
     * @param mixed               $object
     * @param RequestType         $requestType
     * @param AssociationMetadata $associationMetadata
     *
     * @return mixed
     */
    abstract protected function processRelatedObject(
        $object,
        RequestType $requestType,
        AssociationMetadata $associationMetadata
    );

    /**
     * @param array $object
     */
    abstract protected function addRelatedObject(array $object);

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
