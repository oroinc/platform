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
    protected function convertObjectToArray($object, EntityMetadata $metadata = null)
    {
        if (null === $metadata) {
            throw new \InvalidArgumentException('The metadata should be provided.');
        }

        $result = $this->getResourceIdObject(
            $this->getEntityTypeForObject($object, $metadata),
            $this->entityIdAccessor->getEntityId($object, $metadata)
        );

        $data = $this->objectAccessor->toArray($object);
        $this->addMeta($result, $data, $metadata);
        $this->addAttributes($result, $data, $metadata);
        $this->addRelationships($result, $data, $metadata);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertErrorToArray(Error $error)
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
     * {@inheritdoc}
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
     * @param array          $result
     * @param array          $data
     * @param EntityMetadata $metadata
     */
    protected function addMeta(array &$result, array $data, EntityMetadata $metadata)
    {
        $properties = $metadata->getMetaProperties();
        foreach ($properties as $name => $property) {
            if (array_key_exists($name, $data)) {
                $result[self::META][$name] = $data[$name];
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
        $idFieldNames = $metadata->getIdentifierFieldNames();
        $fields = $metadata->getFields();
        foreach ($fields as $name => $field) {
            if (!in_array($name, $idFieldNames, true)) {
                $result[self::ATTRIBUTES][$name] = array_key_exists($name, $data)
                    ? $data[$name]
                    : null;
            }
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
            $result[self::RELATIONSHIPS][$name][self::DATA] = $value;
        }
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
            $this->addRelatedObject($this->convertObjectToArray($targetObject, $targetMetadata));
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

                if ($this->isIdentity($targetMetadata)) {
                    $idOnly = true;
                    $idFieldNames = $targetMetadata->getIdentifierFieldNames();
                    if (count($idFieldNames) === 1) {
                        $object = $this->objectAccessor->getValue($object, reset($idFieldNames));
                    } else {
                        $data = [];
                        foreach ($idFieldNames as $fieldName) {
                            $data[$fieldName] = $this->objectAccessor->getValue($object, $fieldName);
                        }
                        $object = $data;
                    }
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
}
