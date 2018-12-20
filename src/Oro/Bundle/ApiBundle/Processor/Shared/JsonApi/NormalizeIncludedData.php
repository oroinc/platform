<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads data from "included" section of the request data to the context.
 */
class NormalizeIncludedData implements ProcessorInterface
{
    const UPDATE_META = 'update';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityInstantiator */
    protected $entityInstantiator;

    /** @var EntityLoader */
    protected $entityLoader;

    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /** @var EntityIdTransformerRegistry */
    protected $entityIdTransformerRegistry;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var MetadataProvider */
    protected $metadataProvider;

    /** @var FormContext */
    protected $context;

    /** @var EntityMetadata[] */
    private $entityMetadata;

    /**
     * @param DoctrineHelper              $doctrineHelper
     * @param EntityInstantiator          $entityInstantiator
     * @param EntityLoader                $entityLoader
     * @param ValueNormalizer             $valueNormalizer
     * @param EntityIdTransformerRegistry $entityIdTransformerRegistry
     * @param ConfigProvider              $configProvider
     * @param MetadataProvider            $metadataProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityInstantiator $entityInstantiator,
        EntityLoader $entityLoader,
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerRegistry $entityIdTransformerRegistry,
        ConfigProvider $configProvider,
        MetadataProvider $metadataProvider
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityInstantiator = $entityInstantiator;
        $this->entityLoader = $entityLoader;
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext|SingleItemContext $context */

        $includedData = $context->getIncludedData();
        if (null === $includedData) {
            $normalizeIncludedData = $this->normalizeIncludedData($context->getRequestData());
            if (!empty($normalizeIncludedData)) {
                $includedData = $normalizeIncludedData;
                $context->setIncludedData($includedData);
            }
        }

        if (empty($includedData) || null !== $context->getIncludedEntities()) {
            // no included data or they are already converted to included entities collection
            return;
        }

        $this->context = $context;
        $this->entityMetadata = [];
        try {
            $includedEntities = $this->loadIncludedEntities($includedData);
            if (null !== $includedEntities) {
                $includedEntities->setPrimaryEntityId($context->getClassName(), $context->getId());
                $context->setIncludedEntities($includedEntities);
            }
        } finally {
            $this->context = null;
            $this->entityMetadata = null;
        }
    }

    /**
     * @param array $requestData
     *
     * @return array
     */
    protected function normalizeIncludedData(array $requestData)
    {
        $includedData = !empty($requestData[JsonApiDoc::INCLUDED])
            ? $requestData[JsonApiDoc::INCLUDED]
            : [];

        $result = [];
        foreach ($includedData as $index => $data) {
            $result[$index] = [JsonApiDoc::DATA => $data];
        }

        return $result;
    }

    /**
     * @param array $includedData
     *
     * @return IncludedEntityCollection|null
     */
    protected function loadIncludedEntities(array $includedData)
    {
        $includedEntities = new IncludedEntityCollection();

        $includedPointer = $this->buildPointer('', JsonApiDoc::INCLUDED);
        foreach ($includedData as $index => $data) {
            $pointer = $this->buildPointer($includedPointer, $index);
            $data = $this->getDataProperty($data, $pointer);
            $entityClass = $this->getEntityClass(
                $this->buildPointer($pointer, JsonApiDoc::TYPE),
                $data[JsonApiDoc::TYPE]
            );
            if (null !== $entityClass) {
                $updateFlag = $this->getUpdateFlag($pointer, $data);
                if (null !== $updateFlag) {
                    $entityId = $this->getEntityId(
                        $this->buildPointer($pointer, JsonApiDoc::ID),
                        $entityClass,
                        $data[JsonApiDoc::ID],
                        $updateFlag
                    );
                    if (null !== $entityId) {
                        $entity = $this->getEntity($pointer, $entityClass, $entityId, $updateFlag);
                        if (null !== $entity) {
                            $includedEntities->add(
                                $entity,
                                $entityClass,
                                $entityId,
                                new IncludedEntityData($pointer, $index, $updateFlag)
                            );
                        }
                    }
                }
            }
        }

        return !$this->context->hasErrors() ? $includedEntities : null;
    }

    /**
     * @param mixed  $data
     * @param string $pointer
     *
     * @return array
     */
    protected function getDataProperty($data, $pointer)
    {
        if (!is_array($data)) {
            throw new RuntimeException(
                sprintf('The "%s" element should be an array.', $pointer)
            );
        }
        if (!array_key_exists(JsonApiDoc::DATA, $data)) {
            throw new RuntimeException(
                sprintf('The "%s" element should have "%s" property.', $pointer, JsonApiDoc::DATA)
            );
        }
        $data = $data[JsonApiDoc::DATA];
        if (!is_array($data)) {
            throw new RuntimeException(
                sprintf('The "%s" property of "%s" element should be an array.', JsonApiDoc::DATA, $pointer)
            );
        }

        return $data;
    }

    /**
     * @param string $pointer
     * @param array  $data
     *
     * @return bool|null
     */
    protected function getUpdateFlag($pointer, $data)
    {
        if (empty($data[JsonApiDoc::META]) || !array_key_exists(self::UPDATE_META, $data[JsonApiDoc::META])) {
            return false;
        }

        $flag = $data[JsonApiDoc::META][self::UPDATE_META];
        if (true !== $flag && false !== $flag) {
            $this->addValidationError(
                Constraint::VALUE,
                $this->buildPointer($this->buildPointer($pointer, JsonApiDoc::META), self::UPDATE_META),
                'This value should be boolean.'
            );
            $flag = null;
        }

        return $flag;
    }

    /**
     * @param string $pointer
     * @param string $entityType
     *
     * @return string|null
     */
    protected function getEntityClass($pointer, $entityType)
    {
        $entityClass = ValueNormalizerUtil::convertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->context->getRequestType(),
            false
        );
        if ($entityClass) {
            return $entityClass;
        }

        $this->addValidationError(Constraint::ENTITY_TYPE, $pointer);

        return null;
    }

    /**
     * @param string $pointer
     * @param string $entityClass
     * @param mixed  $entityId
     * @param bool   $updateFlag
     *
     * @return mixed
     */
    protected function getEntityId($pointer, $entityClass, $entityId, $updateFlag)
    {
        if (!$updateFlag) {
            return $entityId;
        }

        try {
            return $this->getEntityIdTransformer($this->context->getRequestType())
                ->reverseTransform($entityId, $this->getEntityMetadata($entityClass));
        } catch (\Exception $e) {
            $this->addValidationError(Constraint::ENTITY_ID, $pointer)
                ->setInnerException($e);
        }

        return null;
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

    /**
     * @param string $pointer
     * @param string $entityClass
     * @param mixed  $entityId
     * @param bool   $updateFlag
     *
     * @return object|null
     */
    protected function getEntity($pointer, $entityClass, $entityId, $updateFlag)
    {
        $resolvedEntityClass = $this->doctrineHelper->resolveManageableEntityClass($entityClass);

        if ($updateFlag) {
            if ($resolvedEntityClass) {
                return $this->getExistingEntity($pointer, $resolvedEntityClass, $entityId);
            }

            $this->addValidationError(Constraint::VALUE, $pointer, 'Only manageable entity can be updated.');

            return null;
        }

        return $this->entityInstantiator->instantiate($resolvedEntityClass ?? $entityClass);
    }

    /**
     * @param string $pointer
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return object|null
     */
    protected function getExistingEntity($pointer, $entityClass, $entityId)
    {
        $entity = $this->entityLoader->findEntity($entityClass, $entityId, $this->getEntityMetadata($entityClass));
        if (null === $entity) {
            $this->addValidationError(Constraint::ENTITY, $pointer, 'The entity does not exist.');
        }

        return $entity;
    }

    /**
     * @param string $entityClass
     *
     * @return EntityMetadata
     */
    protected function getEntityMetadata($entityClass)
    {
        if (isset($this->entityMetadata[$entityClass])) {
            return $this->entityMetadata[$entityClass];
        }

        $version = $this->context->getVersion();
        $requestType = $this->context->getRequestType();
        $config = $this->configProvider->getConfig(
            $entityClass,
            $version,
            $requestType,
            [new EntityDefinitionConfigExtra(), new FilterIdentifierFieldsConfigExtra()]
        );

        $metadata = $this->metadataProvider->getMetadata(
            $entityClass,
            $version,
            $requestType,
            $config->getDefinition()
        );

        $this->entityMetadata[$entityClass] = $metadata;

        return $metadata;
    }

    /**
     * @param string $parentPath
     * @param string $property
     *
     * @return string
     */
    protected function buildPointer($parentPath, $property)
    {
        return $parentPath . '/' . $property;
    }

    /**
     * @param string      $title
     * @param string|null $pointer
     * @param string|null $detail
     *
     * @return Error
     */
    protected function addValidationError($title, $pointer = null, $detail = null)
    {
        $error = Error::createValidationError($title);
        if (null !== $pointer) {
            $error->setSource(ErrorSource::createByPointer($pointer));
        }
        if (null !== $detail) {
            $error->setDetail($detail);
        }
        $this->context->addError($error);

        return $error;
    }
}
