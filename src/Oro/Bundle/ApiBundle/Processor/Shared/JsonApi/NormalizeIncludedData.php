<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Doctrine\ORM\NonUniqueResultException;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Util\AclProtectedEntityLoader;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityIdHelper;
use Oro\Bundle\ApiBundle\Util\EntityInstantiator;
use Oro\Bundle\ApiBundle\Util\MetaOperationParser;
use Oro\Bundle\ApiBundle\Util\UpsertCriteriaBuilder;
use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Loads data from "included" section of the request data to the context.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class NormalizeIncludedData implements ProcessorInterface
{
    private DoctrineHelper $doctrineHelper;
    private EntityInstantiator $entityInstantiator;
    private AclProtectedEntityLoader $entityLoader;
    private ValueNormalizer $valueNormalizer;
    private EntityIdTransformerRegistry $entityIdTransformerRegistry;
    private ConfigProvider $configProvider;
    private MetadataProvider $metadataProvider;
    private UpsertCriteriaBuilder $upsertCriteriaBuilder;
    private EntityIdHelper $entityIdHelper;
    private ?FormContext $context = null;
    /** @var EntityDefinitionConfig[] */
    private array $entityConfig = [];
    /** @var EntityMetadata[] */
    private array $entityMetadata = [];

    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityInstantiator $entityInstantiator,
        AclProtectedEntityLoader $entityLoader,
        ValueNormalizer $valueNormalizer,
        EntityIdTransformerRegistry $entityIdTransformerRegistry,
        ConfigProvider $configProvider,
        MetadataProvider $metadataProvider,
        UpsertCriteriaBuilder $upsertCriteriaBuilder,
        EntityIdHelper $entityIdHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityInstantiator = $entityInstantiator;
        $this->entityLoader = $entityLoader;
        $this->valueNormalizer = $valueNormalizer;
        $this->entityIdTransformerRegistry = $entityIdTransformerRegistry;
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
        $this->upsertCriteriaBuilder = $upsertCriteriaBuilder;
        $this->entityIdHelper = $entityIdHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
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
        try {
            $includedEntities = $this->loadIncludedEntities($includedData);
            if (null !== $includedEntities) {
                $includedEntities->setPrimaryEntityId($context->getClassName(), $context->getId());
                $context->setIncludedEntities($includedEntities);
            }
        } finally {
            $this->context = null;
            $this->entityConfig = [];
            $this->entityMetadata = [];
        }
    }

    private function normalizeIncludedData(array $requestData): array
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

    private function loadIncludedEntities(array $includedData): ?IncludedEntityCollection
    {
        $includedEntities = new IncludedEntityCollection();
        $includedPointer = $this->buildPointer('', JsonApiDoc::INCLUDED);
        foreach ($includedData as $index => $data) {
            $pointer = $this->buildPointer($includedPointer, $index);
            $data = $this->getDataProperty($data, $pointer);
            $entityClass = $this->getEntityClass($data[JsonApiDoc::TYPE], $pointer);
            if (null === $entityClass) {
                continue;
            }
            $operationFlags = $this->getOperationFlags($data, $pointer);
            if (null === $operationFlags) {
                continue;
            }
            [$updateFlag, $upsertFlag] = $operationFlags;
            $entityId = $data[JsonApiDoc::ID] ?? null;
            if (null !== $entityId) {
                $entityId = $this->resolveEntityId($entityClass, $entityId, $pointer, $updateFlag, $upsertFlag);
            }

            $this->loadIncludedEntity(
                $includedEntities,
                $entityClass,
                $entityId,
                $updateFlag,
                $upsertFlag,
                $data,
                $index,
                $pointer
            );
        }

        return !$this->context->hasErrors()
            ? $includedEntities
            : null;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function loadIncludedEntity(
        IncludedEntityCollection $includedEntities,
        string $entityClass,
        mixed $entityId,
        ?bool $updateFlag,
        bool|array|null $upsertFlag,
        array $data,
        int $index,
        string $pointer
    ): void {
        $entity = null;
        $isExistingEntity = false;
        $targetAction = null;
        if (\is_array($upsertFlag)) {
            $config = $this->getEntityConfig($entityClass, true);
            $upsertConfig = $config->getUpsertConfig();
            if (!$upsertConfig->isEnabled()) {
                $this->addUpsertFlagValidationError($pointer, 'The upsert operation is not allowed.');
            } else {
                $hasErrors = false;
                $resolvedEntityClass = $this->doctrineHelper->resolveManageableEntityClass($entityClass);
                if ($resolvedEntityClass) {
                    if ($upsertConfig->isAllowedFields($upsertFlag)) {
                        $metadata = $this->getEntityMetadata($entityClass, true);
                        $criteria = $this->getUpsertFindEntityCriteria($metadata, $upsertFlag, $data, $pointer);
                        if (null === $criteria) {
                            $hasErrors = true;
                        } else {
                            try {
                                $entity = $this->entityLoader->findEntityBy(
                                    $resolvedEntityClass,
                                    $criteria,
                                    $config,
                                    $metadata,
                                    $this->context->getRequestType()
                                );
                            } catch (AccessDeniedException $e) {
                                $hasErrors = true;
                                $this->addAccessDeniedValidationError($e, $pointer);
                            } catch (NonUniqueResultException) {
                                $hasErrors = true;
                                $this->addNonUniqueResultValidationError($pointer);
                            }
                            if (null !== $entity) {
                                $isExistingEntity = true;
                                $targetAction = ApiAction::CREATE;
                                if (null === $entityId) {
                                    $entityId = $this->entityIdHelper->getEntityIdentifier($entity, $metadata);
                                }
                            }
                        }
                    } else {
                        $hasErrors = true;
                        $this->addUpsertFlagValidationError(
                            $pointer,
                            $this->getUpsertByFieldsIsNotAllowedErrorMessage($upsertFlag)
                        );
                    }
                } else {
                    $hasErrors = true;
                    $this->addValueValidationError($pointer, 'Only manageable entity can be updated.');
                }
                if (null === $entity && !$hasErrors) {
                    $entity = $this->createNewEntity($entityClass);
                }
            }
        } elseif (null !== $entityId) {
            $hasErrors = false;
            if ($updateFlag || $upsertFlag) {
                if ($upsertFlag) {
                    $upsertConfig = $this->getEntityConfig($entityClass, true)->getUpsertConfig();
                    if (!$upsertConfig->isEnabled()) {
                        $hasErrors = true;
                        $this->addUpsertFlagValidationError($pointer, 'The upsert operation is not allowed.');
                    } elseif (!$upsertConfig->isAllowedById()) {
                        $hasErrors = true;
                        $this->addUpsertFlagValidationError(
                            $pointer,
                            'The upsert operation cannot use the entity identifier to find an entity.'
                        );
                    }
                }
                if (!$hasErrors) {
                    $resolvedEntityClass = $this->doctrineHelper->resolveManageableEntityClass($entityClass);
                    if ($resolvedEntityClass) {
                        try {
                            $entity = $this->entityLoader->findEntity(
                                $resolvedEntityClass,
                                $entityId,
                                $this->getEntityConfig($entityClass),
                                $this->getEntityMetadata($resolvedEntityClass),
                                $this->context->getRequestType()
                            );
                        } catch (AccessDeniedException $e) {
                            $hasErrors = true;
                            $this->addAccessDeniedValidationError($e, $pointer);
                        } catch (NonUniqueResultException) {
                            $hasErrors = true;
                            $this->addNonUniqueResultValidationError($pointer);
                        }
                        if (!$hasErrors) {
                            if (null !== $entity) {
                                $isExistingEntity = true;
                            } elseif ($updateFlag) {
                                $hasErrors = true;
                                $this->addValidationError(Constraint::ENTITY, $pointer, 'The entity does not exist.');
                            }
                        }
                    } else {
                        $hasErrors = true;
                        $this->addValueValidationError($pointer, 'Only manageable entity can be updated.');
                    }
                }
            }
            if (null === $entity && !$updateFlag && !$hasErrors) {
                $entity = $this->createNewEntity($entityClass);
            }
        }
        if (null !== $entity) {
            $includedEntities->add(
                $entity,
                $entityClass,
                $entityId,
                new IncludedEntityData($pointer, $index, $isExistingEntity, $targetAction)
            );
        }
    }

    private function getDataProperty(mixed $data, string $pointer): array
    {
        if (!\is_array($data)) {
            throw new RuntimeException(sprintf('The "%s" element should be an array.', $pointer));
        }
        if (!\array_key_exists(JsonApiDoc::DATA, $data)) {
            throw new RuntimeException(sprintf(
                'The "%s" element should have "%s" property.',
                $pointer,
                JsonApiDoc::DATA
            ));
        }
        $data = $data[JsonApiDoc::DATA];
        if (!\is_array($data)) {
            throw new RuntimeException(sprintf(
                'The "%s" property of "%s" element should be an array.',
                JsonApiDoc::DATA,
                $pointer
            ));
        }

        return $data;
    }

    private function getOperationFlags(array $data, string $pointer): ?array
    {
        $meta = !empty($data[JsonApiDoc::META]) && \is_array($data[JsonApiDoc::META])
            ? $data[JsonApiDoc::META]
            : [];

        return MetaOperationParser::getOperationFlags(
            $meta,
            JsonApiDoc::META_UPDATE,
            JsonApiDoc::META_UPSERT,
            $this->buildPointer($pointer, JsonApiDoc::META),
            $this->context
        );
    }

    private function getEntityClass(string $entityType, string $pointer): ?string
    {
        $entityClass = ValueNormalizerUtil::tryConvertToEntityClass(
            $this->valueNormalizer,
            $entityType,
            $this->context->getRequestType()
        );
        if ($entityClass) {
            return $entityClass;
        }

        $this->addValidationError(
            Constraint::ENTITY_TYPE,
            $this->buildPointer($pointer, JsonApiDoc::TYPE),
            sprintf('Unknown entity type: %s.', $entityType)
        );

        return null;
    }

    private function resolveEntityId(
        string $entityClass,
        mixed $entityId,
        string $pointer,
        ?bool $updateFlag,
        bool|array|null $upsertFlag
    ): mixed {
        if ($updateFlag) {
            $entityId = $this->normalizeEntityId($entityClass, $entityId, $pointer);
        } elseif (true === $upsertFlag && !$this->getEntityMetadata($entityClass)->hasIdentifierGenerator()) {
            $entityId = $this->normalizeEntityId($entityClass, $entityId, $pointer);
        }

        return $entityId;
    }

    private function normalizeEntityId(string $entityClass, mixed $entityId, string $pointer): mixed
    {
        try {
            return $this->getEntityIdTransformer($this->context->getRequestType())
                ->reverseTransform($entityId, $this->getEntityMetadata($entityClass));
        } catch (\Exception $e) {
            $this->addValidationError(Constraint::ENTITY_ID, $this->buildPointer($pointer, JsonApiDoc::ID))
                ->setInnerException($e);
        }

        return null;
    }

    private function getEntityIdTransformer(RequestType $requestType): EntityIdTransformerInterface
    {
        return $this->entityIdTransformerRegistry->getEntityIdTransformer($requestType);
    }

    private function getUpsertFindEntityCriteria(
        EntityMetadata $metadata,
        array $identityFieldNames,
        array $data,
        string $pointer
    ): ?array {
        $entityData = [];
        $attributes = $data[JsonApiDoc::ATTRIBUTES] ?? [];
        $relationships = $data[JsonApiDoc::RELATIONSHIPS] ?? [];
        foreach ($identityFieldNames as $fieldName) {
            if (\array_key_exists($fieldName, $attributes)) {
                $entityData[$fieldName] = $attributes[$fieldName];
            } elseif (\array_key_exists($fieldName, $relationships)) {
                $entityData[$fieldName] = $relationships[$fieldName];
            }
        }

        return $this->upsertCriteriaBuilder->getUpsertFindEntityCriteria(
            $metadata,
            $identityFieldNames,
            $entityData,
            $this->buildUpsertFlagPointer($pointer),
            $this->context
        );
    }

    private function createNewEntity(string $entityClass): object
    {
        $resolvedEntityClass = $this->resolveNewEntityClass($entityClass)
            ?? $this->doctrineHelper->resolveManageableEntityClass($entityClass)
            ?? $entityClass;

        return $this->entityInstantiator->instantiate($resolvedEntityClass);
    }

    private function resolveNewEntityClass(string $entityClass): ?string
    {
        $formOptions = $this->getEntityConfig($entityClass, false, ApiAction::CREATE)->getFormOptions();
        $formDataClass = $formOptions['data_class'] ?? null;
        if (!$formDataClass || $formDataClass === $entityClass) {
            return null;
        }

        return $formDataClass;
    }

    private function getEntityConfig(
        string $entityClass,
        bool $full = false,
        string $action = null
    ): EntityDefinitionConfig {
        $cacheKey = $entityClass . ($action ? ':' . $action : '') . ($full ? ':full' : '');
        if (isset($this->entityConfig[$cacheKey])) {
            return $this->entityConfig[$cacheKey];
        }

        $configExtras = [new EntityDefinitionConfigExtra($action)];
        if (!$full) {
            $configExtras[] = new FilterIdentifierFieldsConfigExtra();
        }
        $config = $this->configProvider->getConfig(
            $entityClass,
            $this->context->getVersion(),
            $this->context->getRequestType(),
            $configExtras
        )->getDefinition();
        if (null === $config) {
            throw new \RuntimeException(sprintf(
                'The entity config for the "%s" entity was not found.',
                $entityClass
            ));
        }

        $this->entityConfig[$cacheKey] = $config;

        return $config;
    }

    private function getEntityMetadata(string $entityClass, bool $full = false): EntityMetadata
    {
        $cacheKey = $entityClass . ($full ? ':full' : '');
        if (isset($this->entityMetadata[$cacheKey])) {
            return $this->entityMetadata[$cacheKey];
        }

        $metadata = $this->metadataProvider->getMetadata(
            $entityClass,
            $this->context->getVersion(),
            $this->context->getRequestType(),
            $this->getEntityConfig($entityClass, $full)
        );

        $this->entityMetadata[$cacheKey] = $metadata;

        return $metadata;
    }

    private function buildPointer(string $parentPointer, string $property): string
    {
        return $parentPointer . '/' . $property;
    }

    private function buildUpsertFlagPointer(string $parentPointer): string
    {
        return $this->buildPointer($this->buildPointer($parentPointer, JsonApiDoc::META), JsonApiDoc::META_UPSERT);
    }

    private function addValidationError(string $title, string $pointer, ?string $detail = null): Error
    {
        $error = Error::createValidationError($title, $detail)
            ->setSource(ErrorSource::createByPointer($pointer));
        $this->context->addError($error);

        return $error;
    }

    private function addValueValidationError(string $pointer, string $detail): void
    {
        $this->addValidationError(Constraint::VALUE, $pointer, $detail);
    }

    private function addUpsertFlagValidationError(string $pointer, string $detail): void
    {
        $this->addValueValidationError($this->buildUpsertFlagPointer($pointer), $detail);
    }

    private function addNonUniqueResultValidationError(string $pointer): void
    {
        $this->context->addError(
            Error::createConflictValidationError(
                'The upsert operation founds more than one entity.'
            )->setSource(ErrorSource::createByPointer($pointer))
        );
    }

    private function addAccessDeniedValidationError(AccessDeniedException $e, string $pointer): void
    {
        $this->context->addError(
            Error::createByException($e)->setSource(ErrorSource::createByPointer($pointer))
        );
    }

    private function getUpsertByFieldsIsNotAllowedErrorMessage(array $fieldNames): string
    {
        return
            'The upsert operation cannot use '
            . (\count($fieldNames) > 1 ? 'these fields' : 'this field')
            . ' to find an entity.';
    }
}
