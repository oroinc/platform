<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocument\EntityIdAccessor;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocument\ObjectAccessor;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocument\ObjectAccessorInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

class JsonApiDocumentBuilder
{
    const JSONAPI       = 'jsonapi';
    const LINKS         = 'links';
    const META          = 'meta';
    const ERRORS        = 'errors';
    const DATA          = 'data';
    const INCLUDED      = 'included';
    const ATTRIBUTES    = 'attributes';
    const RELATIONSHIPS = 'relationships';
    const ID            = 'id';
    const TYPE          = 'type';

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /** @var ObjectAccessorInterface */
    protected $objectAccessor;

    /** @var EntityIdAccessor */
    protected $entityIdAccessor;

    /** @var array */
    protected $result = [];

    /** @var RequestType */
    protected $requestType;

    /**
     * @param ValueNormalizer              $valueNormalizer
     * @param EntityIdTransformerInterface $entityIdTransformer
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerInterface $entityIdTransformer
    ) {
        $this->valueNormalizer     = $valueNormalizer;
        $this->entityIdTransformer = $entityIdTransformer;

        $this->objectAccessor   = new ObjectAccessor();
        $this->entityIdAccessor = new EntityIdAccessor(
            $this->objectAccessor,
            $this->entityIdTransformer
        );
        $this->requestType      = new RequestType([RequestType::JSON_API]);
    }

    /**
     * Returns built JSON API document.
     *
     * @return array
     */
    public function getDocument()
    {
        return $this->result;
    }

    /**
     * Sets a single object as the primary data.
     *
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     *
     * @return self
     */
    public function setDataObject($object, EntityMetadata $metadata = null)
    {
        $this->assertNoData();

        $this->result[self::DATA] = null;
        if (null !== $object) {
            $this->result[self::DATA] = $this->handleObject($object, $metadata);
        }

        return $this;
    }

    /**
     * Sets a collection as the primary data.
     *
     * @param mixed               $collection
     * @param EntityMetadata|null $metadata
     *
     * @return self
     */
    public function setDataCollection($collection, EntityMetadata $metadata = null)
    {
        $this->assertNoData();

        $this->result[self::DATA] = [];
        if (is_array($collection) || $collection instanceof \Traversable) {
            foreach ($collection as $object) {
                $this->result[self::DATA][] = $this->handleObject($object, $metadata);
            }
        } else {
            throw $this->createUnexpectedValueException('array or \Traversable', $collection);
        }

        return $this;
    }

    /**
     * Sets error.
     *
     * @param Error $error
     *
     * @return self
     */
    public function setErrorObject(Error $error)
    {
        $this->assertNoData();

        $this->result[self::ERRORS] = [$this->handleError($error)];

        return $this;
    }

    /**
     * Sets errors collection.
     *
     * @param Error[] $errors
     *
     * @return self
     */
    public function setErrorCollection(array $errors)
    {
        $this->assertNoData();

        $errorsData = [];
        foreach ($errors as $error) {
            $errorsData[] = $this->handleError($error);
        }
        $this->result[self::ERRORS] = $errorsData;

        return $this;
    }

    /**
     * Adds an object related to the primary data and/or another related object.
     *
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     *
     * @return self
     */
    protected function addRelatedObject($object, EntityMetadata $metadata = null)
    {
        $this->result[self::INCLUDED][] = $this->handleObject($object, $metadata);

        return $this;
    }

    /**
     * @param mixed               $object
     * @param EntityMetadata|null $metadata
     *
     * @return array
     */
    protected function handleObject($object, EntityMetadata $metadata = null)
    {
        $result = [];
        if (null === $metadata) {
            $result[self::ATTRIBUTES] = $this->objectAccessor->toArray($object);
        } else {
            $className  = $this->objectAccessor->getClassName($object);
            $entityType = $className
                ? $this->getEntityType($className, $metadata->getClassName())
                : $this->getEntityType($metadata->getClassName());

            $result = $this->getResourceIdObject(
                $entityType,
                $this->entityIdAccessor->getEntityId($object, $metadata)
            );

            $data = $this->objectAccessor->toArray($object);
            foreach ($data as $name => $value) {
                if (in_array($name, $metadata->getIdentifierFieldNames(), true)) {
                    continue;
                }
                if ($metadata->hasAssociation($name)) {
                    $associationMetadata = $metadata->getAssociation($name);

                    $result[self::RELATIONSHIPS][$name][self::DATA] = $associationMetadata->isCollection()
                        ? $this->handleRelatedCollection($value, $associationMetadata)
                        : $this->handleRelatedObject($value, $associationMetadata);
                } else {
                    $result[self::ATTRIBUTES][$name] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * @param mixed               $object
     * @param AssociationMetadata $associationMetadata
     *
     * @return array|null
     */
    protected function handleRelatedObject($object, AssociationMetadata $associationMetadata)
    {
        if (null === $object) {
            return null;
        }

        return $this->processRelatedObject($object, $associationMetadata);
    }

    /**
     * @param mixed               $collection
     * @param AssociationMetadata $associationMetadata
     *
     * @return array
     */
    protected function handleRelatedCollection($collection, AssociationMetadata $associationMetadata)
    {
        if (null === $collection) {
            return [];
        }

        $data = [];
        foreach ($collection as $object) {
            $data[] = $this->processRelatedObject($object, $associationMetadata);
        }

        return $data;
    }

    /**
     * @param mixed               $object
     * @param AssociationMetadata $associationMetadata
     *
     * @return array The resource identifier
     */
    protected function processRelatedObject($object, AssociationMetadata $associationMetadata)
    {
        $targetMetadata = $associationMetadata->getTargetMetadata();

        $preparedValue = $this->prepareRelatedValue(
            $object,
            $associationMetadata->getTargetClassName(),
            $targetMetadata
        );
        if ($preparedValue['idOnly']) {
            $resourceId = $this->getResourceIdObject(
                $preparedValue['entityType'],
                $this->entityIdTransformer->transform($preparedValue['value'])
            );
        } else {
            $resourceId = $this->getResourceIdObject(
                $preparedValue['entityType'],
                $this->entityIdAccessor->getEntityId($preparedValue['value'], $targetMetadata)
            );
            $this->addRelatedObject($preparedValue['value'], $targetMetadata);
        }

        return $resourceId;
    }

    /**
     * @param mixed               $object
     * @param string              $targetClassName
     * @param EntityMetadata|null $targetMetadata
     *
     * @return array
     */
    protected function prepareRelatedValue($object, $targetClassName, EntityMetadata $targetMetadata = null)
    {
        $idOnly           = false;
        $targetEntityType = null;
        if (is_array($object) || is_object($object)) {
            if (null !== $targetMetadata) {
                if ($targetMetadata->isInheritedType()) {
                    $targetEntityType = $this->getEntityType(
                        $this->objectAccessor->getClassName($object),
                        $targetClassName
                    );
                }

                $data = $this->objectAccessor->toArray($object);
                if ($this->isIdentity($data, $targetMetadata)) {
                    $idOnly = true;

                    $object = count($data) === 1
                        ? reset($data)
                        : $data;
                }
            }
        } else {
            $idOnly = true;
        }
        if (!$targetEntityType) {
            $targetEntityType = $this->getEntityType($targetClassName);
        }

        return [
            'value'      => $object,
            'entityType' => $targetEntityType,
            'idOnly'     => $idOnly
        ];
    }

    /**
     * @param string $entityType
     * @param string $entityId
     *
     * @return array
     */
    protected function getResourceIdObject($entityType, $entityId)
    {
        return [
            self::TYPE => $entityType,
            self::ID   => $entityId
        ];
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
     * @param string $entityClass
     * @param bool   $throwException
     *
     * @return string|null
     */
    protected function convertToEntityType($entityClass, $throwException = true)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $this->requestType,
            $throwException
        );
    }

    /**
     * Checks whether a given object has only identity property(s)
     * or any other properties as well.
     *
     * @param array          $object
     * @param EntityMetadata $metadata
     *
     * @return bool
     */
    protected function isIdentity(array $object, EntityMetadata $metadata)
    {
        $idFields = $metadata->getIdentifierFieldNames();

        return
            count($object) === count($idFields)
            && count(array_diff_key($object, array_flip($idFields))) === 0;
    }

    /**
     * Checks that the primary data does not exist.
     */
    protected function assertNoData()
    {
        if (array_key_exists(self::DATA, $this->result)) {
            throw new \RuntimeException('A primary data already exist.');
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

    /**
     * @param Error $error
     *
     * @return array
     */
    protected function handleError(Error $error)
    {
        $result = [];
        if ($error->getStatusCode()) {
            $result['code'] = (string)$error->getStatusCode();
        }
        if ($error->getDetail()) {
            $result['detail'] = $error->getDetail();
        }
        if ($error->getTitle()) {
            $result['title'] = $error->getTitle();
        }

        return $result;
    }
}
