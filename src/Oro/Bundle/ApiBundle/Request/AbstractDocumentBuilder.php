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
            $this->result[self::DATA] = $this->transformObjectToArray($object, $metadata);
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
            foreach ($collection as $object) {
                $this->result[self::DATA][] = null === $object || is_scalar($object)
                    ? $object
                    : $this->transformObjectToArray($object, $metadata);
            }
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

        $this->result[self::ERRORS] = [$this->transformErrorToArray($error)];
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorCollection(array $errors)
    {
        $this->assertNoData();

        $errorsData = [];
        foreach ($errors as $error) {
            $errorsData[] = $this->transformErrorToArray($error);
        }
        $this->result[self::ERRORS] = $errorsData;
    }

    /**
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    abstract protected function transformObjectToArray($object, EntityMetadata $metadata = null);

    /**
     * @param Error $error
     *
     * @return array
     */
    abstract protected function transformErrorToArray(Error $error);

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
