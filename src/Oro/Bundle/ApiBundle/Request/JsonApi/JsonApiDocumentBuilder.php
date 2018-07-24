<?php

namespace Oro\Bundle\ApiBundle\Request\JsonApi;

use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Request\AbstractDocumentBuilder;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\DocumentBuilder\EntityIdAccessor;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;

/**
 * The document builder for REST API response conforms JSON.API specification.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class JsonApiDocumentBuilder extends AbstractDocumentBuilder
{
    public const JSONAPI       = 'jsonapi';
    public const LINKS         = 'links';
    public const META          = 'meta';
    public const INCLUDED      = 'included';
    public const ATTRIBUTES    = 'attributes';
    public const RELATIONSHIPS = 'relationships';
    public const ID            = 'id';
    public const TYPE          = 'type';

    private const ERROR_STATUS    = 'status';
    private const ERROR_CODE      = 'code';
    private const ERROR_TITLE     = 'title';
    private const ERROR_DETAIL    = 'detail';
    private const ERROR_SOURCE    = 'source';
    private const ERROR_POINTER   = 'pointer';
    private const ERROR_PARAMETER = 'parameter';

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerRegistry */
    private $entityIdTransformerRegistry;

    /** @var EntityIdAccessor */
    protected $entityIdAccessor;

    /**
     * @param ValueNormalizer             $valueNormalizer
     * @param EntityIdTransformerRegistry $entityIdTransformerRegistry
     */
    public function __construct(
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerRegistry $entityIdTransformerRegistry
    ) {
        parent::__construct();

        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;

        $this->entityIdAccessor = new EntityIdAccessor(
            $this->objectAccessor,
            $this->entityIdTransformerRegistry
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getDocument()
    {
        $result = $this->result;
        // check whether the result document data contains only a meta information
        if (\array_key_exists(self::DATA, $result)) {
            $data = $result[self::DATA];
            if (\is_array($data) && \array_key_exists(self::META, $data) && \count($data) === 1) {
                $result[self::META] = $data[self::META];
                unset($result[self::DATA]);
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertCollectionToArray($collection, RequestType $requestType, EntityMetadata $metadata = null)
    {
        if (null !== $metadata) {
            return parent::convertCollectionToArray($collection, $requestType, $metadata);
        }

        $items = [];
        foreach ($collection as $object) {
            $item = $this->convertObjectToArray($object, $requestType);
            $items[] = $item[self::META];
        }

        return [self::META => [self::DATA => $items]];
    }

    /**
     * {@inheritdoc}
     */
    protected function convertObjectToArray($object, RequestType $requestType, EntityMetadata $metadata = null)
    {
        $data = $this->objectAccessor->toArray($object);
        if (null === $metadata) {
            $result = [self::META => $data];
        } elseif ($metadata->hasIdentifierFields()) {
            $result = $this->getResourceIdObject(
                $this->getEntityTypeForObject($object, $requestType, $metadata),
                $this->entityIdAccessor->getEntityId($object, $metadata, $requestType)
            );
            $this->addMeta($result, $data, $metadata);
            $this->addAttributes($result, $data, $metadata);
            $this->addRelationships($result, $data, $requestType, $metadata);
        } else {
            $result = [];
            $this->addMeta($result, $data, $metadata);
            $this->addAttributesAsMeta($result, $data, $metadata);
            $this->addRelationshipsAsMeta($result, $data, $requestType, $metadata);
        }

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
    protected function convertToEntityType($entityClass, RequestType $requestType, $throwException = true)
    {
        return ValueNormalizerUtil::convertToEntityType(
            $this->valueNormalizer,
            $entityClass,
            $requestType,
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
            if (!$property->isOutput()) {
                continue;
            }
            $resultName = $property->getResultName();
            if (array_key_exists($name, $data)) {
                $result[self::META][$resultName] = $data[$name];
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
            if (!$field->isOutput()) {
                continue;
            }
            if (!in_array($name, $idFieldNames, true)) {
                $result[self::ATTRIBUTES][$name] = $data[$name] ?? null;
            }
        }
    }

    /**
     * @param array          $result
     * @param array          $data
     * @param RequestType    $requestType
     * @param EntityMetadata $metadata
     */
    protected function addRelationships(
        array &$result,
        array $data,
        RequestType $requestType,
        EntityMetadata $metadata
    ) {
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $association) {
            if (!$association->isOutput()) {
                continue;
            }
            $value = $this->getRelationshipValue($data, $requestType, $name, $association);
            if (DataType::isAssociationAsField($association->getDataType())) {
                $result[self::ATTRIBUTES][$name] = $value;
            } else {
                $result[self::RELATIONSHIPS][$name][self::DATA] = $value;
            }
        }
    }

    /**
     * @param array          $result
     * @param array          $data
     * @param EntityMetadata $metadata
     */
    protected function addAttributesAsMeta(array &$result, array $data, EntityMetadata $metadata)
    {
        $fields = $metadata->getFields();
        foreach ($fields as $name => $field) {
            if (!$field->isOutput()) {
                continue;
            }
            $result[self::META][$name] = $data[$name] ?? null;
        }
    }

    /**
     * @param array          $result
     * @param array          $data
     * @param RequestType    $requestType
     * @param EntityMetadata $metadata
     */
    protected function addRelationshipsAsMeta(
        array &$result,
        array $data,
        RequestType $requestType,
        EntityMetadata $metadata
    ) {
        $associations = $metadata->getAssociations();
        foreach ($associations as $name => $association) {
            if (!$association->isOutput()) {
                continue;
            }
            $result[self::META][$name] = $this->getRelationshipValue($data, $requestType, $name, $association);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function processRelatedObject(
        $object,
        RequestType $requestType,
        AssociationMetadata $associationMetadata
    ) {
        $targetMetadata = $associationMetadata->getTargetMetadata();

        $preparedValue = $this->prepareRelatedValue(
            $object,
            $requestType,
            $associationMetadata->getTargetClassName(),
            $targetMetadata
        );
        if ($preparedValue['idOnly']) {
            $resourceId = $this->getResourceIdObject(
                $preparedValue['entityType'],
                $this->getEntityIdTransformer($requestType)->transform($preparedValue['value'], $targetMetadata)
            );
        } else {
            $targetObject = $preparedValue['value'];
            $resourceId = $this->getResourceIdObject(
                $preparedValue['entityType'],
                $this->entityIdAccessor->getEntityId($targetObject, $targetMetadata, $requestType)
            );
            $this->addRelatedObject($this->convertObjectToArray($targetObject, $requestType, $targetMetadata));
        }

        return $resourceId;
    }

    /**
     * @param mixed               $object
     * @param RequestType         $requestType
     * @param string              $targetClassName
     * @param EntityMetadata|null $targetMetadata
     *
     * @return array
     */
    protected function prepareRelatedValue(
        $object,
        RequestType $requestType,
        $targetClassName,
        EntityMetadata $targetMetadata = null
    ) {
        $idOnly = false;
        $targetEntityType = null;
        if (is_array($object) || is_object($object)) {
            if (null !== $targetMetadata) {
                if ($targetMetadata->isInheritedType()) {
                    $targetEntityType = $this->getEntityType(
                        $this->objectAccessor->getClassName($object),
                        $requestType,
                        $targetClassName
                    );
                }

                if ($this->hasIdentifierFieldsOnly($targetMetadata)) {
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
            $targetEntityType = $this->getEntityType($targetClassName, $requestType);
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
     * @param RequestType $requestType
     *
     * @return EntityIdTransformerInterface
     */
    protected function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }
}
