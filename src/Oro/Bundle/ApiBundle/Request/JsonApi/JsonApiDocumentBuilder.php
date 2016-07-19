<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\EntityIdAccessor;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

class JsonApiDocumentBuilder extends AbstractDocumentBuilder
{
    const JSONAPI       = 'jsonapi';
    const LINKS         = 'links';
    const META          = 'meta';
    const INCLUDED      = 'included';
    const ATTRIBUTES    = 'attributes';
    const RELATIONSHIPS = 'relationships';
    const ID            = 'id';
    const TYPE          = 'type';

    const ERROR_STATUS    = 'status';
    const ERROR_CODE      = 'code';
    const ERROR_TITLE     = 'title';
    const ERROR_DETAIL    = 'detail';
    const ERROR_SOURCE    = 'source';
    const ERROR_POINTER   = 'pointer';
    const ERROR_PARAMETER = 'parameter';

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerInterface */
    protected $entityIdTransformer;

    /** @var EntityIdAccessor */
    protected $entityIdAccessor;

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
        parent::__construct();

        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformer = $entityIdTransformer;

        $this->entityIdAccessor = new EntityIdAccessor(
            $this->objectAccessor,
            $this->entityIdTransformer
        );
        $this->requestType = new RequestType([RequestType::JSON_API]);
    }

    /**
     * {@inheritdoc}
     */
    protected function transformObjectToArray($object, EntityMetadata $metadata = null)
    {
        $result = [];
        if (null === $metadata) {
            $result[self::ATTRIBUTES] = $this->objectAccessor->toArray($object);
        } else {
            $className = $this->objectAccessor->getClassName($object);
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
                if ($metadata->hasField($name)) {
                    $result[self::ATTRIBUTES][$name] = $value;
                } elseif ($metadata->hasAssociation($name)) {
                    $associationMetadata = $metadata->getAssociation($name);
                    $result[self::RELATIONSHIPS][$name][self::DATA] = $associationMetadata->isCollection()
                        ? $this->handleRelatedCollection($value, $associationMetadata)
                        : $this->handleRelatedObject($value, $associationMetadata);
                } elseif ($metadata->hasMetaProperty($name)) {
                    $result[self::META][$name] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function transformErrorToArray(Error $error)
    {
        $result = [];

        if ($error->getStatusCode()) {
            $result[self::ERROR_STATUS] = (string)$error->getStatusCode();
        }
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
                $result[self::ERROR_SOURCE][self::ERROR_POINTER] = $source->getPointer();
            } elseif ($source->getParameter()) {
                $result[self::ERROR_SOURCE][self::ERROR_PARAMETER] = $source->getParameter();
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
            $targetObject = $preparedValue['value'];
            $resourceId = $this->getResourceIdObject(
                $preparedValue['entityType'],
                $this->entityIdAccessor->getEntityId($targetObject, $targetMetadata)
            );
            $this->addRelatedObject($this->transformObjectToArray($targetObject, $targetMetadata));
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
        $idOnly = false;
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
     * @param array $object
     */
    protected function addRelatedObject(array $object)
    {
        // check whether this object was already added
        if (!empty($this->result[self::INCLUDED])) {
            $entityType = $object[self::TYPE];
            $entityId = $object[self::ID];
            foreach ($this->result[self::INCLUDED] as $existingObject) {
                if ($existingObject[self::TYPE] === $entityType && $existingObject[self::ID] === $entityId) {
                    return;
                }
            }
        }

        $this->result[self::INCLUDED][] = $object;
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
}
