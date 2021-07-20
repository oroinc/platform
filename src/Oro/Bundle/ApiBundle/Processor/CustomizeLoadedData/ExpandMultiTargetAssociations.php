<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\AssociationConfigUtil;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\TargetConfigExtraBuilder;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;
use Oro\Component\EntitySerializer\SerializationHelper;

/**
 * Checks if expanding of a multi-target association was requested,
 * and if so, load data for it.
 * @see \Oro\Bundle\ApiBundle\Metadata\TargetMetadataAccessor
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExpandMultiTargetAssociations implements ProcessorInterface
{
    /** @var ConfigProvider */
    private $configProvider;

    /** @var EntitySerializer */
    private $entitySerializer;

    /** @var SerializationHelper */
    private $serializationHelper;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ResourcesProvider */
    private $resourcesProvider;

    public function __construct(
        ConfigProvider $configProvider,
        EntitySerializer $entitySerializer,
        SerializationHelper $serializationHelper,
        DoctrineHelper $doctrineHelper,
        ResourcesProvider $resourcesProvider
    ) {
        $this->configProvider = $configProvider;
        $this->entitySerializer = $entitySerializer;
        $this->serializationHelper = $serializationHelper;
        $this->doctrineHelper = $doctrineHelper;
        $this->resourcesProvider = $resourcesProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $expandedData = $this->expandMultiTargetAssociations($context);
        if (null !== $expandedData) {
            $context->setData($expandedData);
        }
    }

    private function expandMultiTargetAssociations(CustomizeLoadedDataContext $context): ?array
    {
        $config = $context->getConfig();
        if (null === $config) {
            return null;
        }

        $data = $context->getData();
        $entityIdFieldNames = $config->getIdentifierFieldNames();
        $entityIdFieldName = reset($entityIdFieldNames);

        if (\is_a($context->getClassName(), EntityIdentifier::class, true)) {
            return $this->loadExpandedEntities($data, $entityIdFieldName, $context);
        }

        return $this->loadExpandedEntitiesForAssociations(
            $data,
            $entityIdFieldName,
            $context,
            $this->getAssociationsToExpand($config, $context)
        );
    }

    private function isExpandRequested(CustomizeLoadedDataContext $context): bool
    {
        $associationPath = $context->getPropertyPath();
        if (!$associationPath && $this->getSubresourceAssociationName($context)) {
            return true;
        }

        /** @var ExpandRelatedEntitiesConfigExtra|null $expandConfigExtra */
        $expandConfigExtra = $context->getConfigExtra(ExpandRelatedEntitiesConfigExtra::NAME);
        if (null === $expandConfigExtra) {
            return false;
        }

        return $expandConfigExtra->isExpandRequested($this->getAssociationName($associationPath));
    }

    private function getPathPrefix(CustomizeLoadedDataContext $context): ?string
    {
        $propertyPath = $context->getPropertyPath();
        if (!$propertyPath) {
            return null;
        }

        return $propertyPath . ConfigUtil::PATH_DELIMITER;
    }

    private function getSubresourceAssociationName(CustomizeLoadedDataContext $context): ?string
    {
        /** @var EntityDefinitionConfigExtra|null $entityConfigExtra */
        $entityConfigExtra = $context->getConfigExtra(EntityDefinitionConfigExtra::NAME);
        if (null === $entityConfigExtra || $entityConfigExtra->getAction() !== ApiAction::GET_SUBRESOURCE) {
            return null;
        }

        return $entityConfigExtra->getAssociationName();
    }

    private function getAssociationName(string $associationPath): string
    {
        $lastDelimiter = strrpos($associationPath, ConfigUtil::PATH_DELIMITER);
        if (false !== $lastDelimiter) {
            return substr($associationPath, $lastDelimiter + 1);
        }

        return $associationPath;
    }

    /**
     * @param EntityDefinitionConfig     $config
     * @param CustomizeLoadedDataContext $context
     *
     * @return EntityDefinitionFieldConfig[] [association name => EntityDefinitionFieldConfig, ...]
     */
    private function getAssociationsToExpand(
        EntityDefinitionConfig $config,
        CustomizeLoadedDataContext $context
    ): array {
        if (!$context->getPropertyPath()) {
            $subresourceAssociationName = $this->getSubresourceAssociationName($context);
            if ($subresourceAssociationName) {
                $associationConfig = $config->getField($subresourceAssociationName);
                if (null !== $associationConfig) {
                    $dataType = $associationConfig->getDataType();
                    if (DataType::isExtendedAssociation($dataType) || DataType::isNestedAssociation($dataType)) {
                        return [$subresourceAssociationName => $associationConfig];
                    }
                    $targetClass = AssociationConfigUtil::getAssociationTargetClass($associationConfig, $config);
                    if ($targetClass && \is_a($targetClass, EntityIdentifier::class, true)) {
                        return [$subresourceAssociationName => $associationConfig];
                    }
                }
            }
        }

        return $this->collectAssociationsToExpand($config, $context);
    }

    /**
     * @param EntityDefinitionConfig     $config
     * @param CustomizeLoadedDataContext $context
     *
     * @return EntityDefinitionFieldConfig[] [association name => EntityDefinitionFieldConfig, ...]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function collectAssociationsToExpand(
        EntityDefinitionConfig $config,
        CustomizeLoadedDataContext $context
    ): array {
        $result = [];
        /** @var ExpandRelatedEntitiesConfigExtra|null $expandConfigExtra */
        $expandConfigExtra = $context->getConfigExtra(ExpandRelatedEntitiesConfigExtra::NAME);
        if (null !== $expandConfigExtra) {
            $pathPrefix = $this->getPathPrefix($context);
            $fields = $config->getFields();
            foreach ($fields as $fieldName => $field) {
                if ($field->isExcluded()) {
                    continue;
                }
                $targetClass = AssociationConfigUtil::getAssociationTargetClass($field, $config);
                if (!$targetClass || !\is_a($targetClass, EntityIdentifier::class, true)) {
                    continue;
                }
                if ($field->isCollectionValuedAssociation()
                    && !DataType::isExtendedAssociation($field->getDataType())
                ) {
                    continue;
                }
                $fieldPath = $fieldName;
                if ($pathPrefix) {
                    $fieldPath = $pathPrefix . $fieldPath;
                }
                if (!$expandConfigExtra->isExpandRequested($fieldPath)) {
                    continue;
                }

                $result[$fieldName] = $field;
            }
        }

        return $result;
    }

    /**
     * @param array  $data
     * @param string $entityIdFieldName
     *
     * @return array [entity class => [entity id, ...], ...]
     */
    private function getEntityIds(array $data, string $entityIdFieldName): array
    {
        $result = [];
        foreach ($data as $item) {
            $result[$item[ConfigUtil::CLASS_NAME]][] = $item[$entityIdFieldName];
        }

        return $result;
    }

    /**
     * @param array                         $data
     * @param string                        $entityIdFieldName
     * @param EntityDefinitionFieldConfig[] $associations [association name => EntityDefinitionFieldConfig, ...]
     *
     * @return array [association name => [entity class => [entity id, ...], ...], ...]
     */
    private function getEntityIdsForAssociations(array $data, string $entityIdFieldName, array $associations): array
    {
        $allIds = [];
        foreach ($data as $item) {
            foreach ($associations as $associationName => $associationConfig) {
                if (!isset($item[$associationName])) {
                    continue;
                }
                $associationData = $item[$associationName];
                if (!$associationData) {
                    continue;
                }
                if ($associationConfig->isCollectionValuedAssociation()) {
                    foreach ($associationData as $dataItem) {
                        $entityClass = $dataItem[ConfigUtil::CLASS_NAME];
                        $allIds[$associationName][$entityClass][] = $dataItem[$entityIdFieldName];
                    }
                } else {
                    $entityClass = $associationData[ConfigUtil::CLASS_NAME];
                    $allIds[$associationName][$entityClass][] = $associationData[$entityIdFieldName];
                }
            }
        }

        $result = [];
        foreach ($allIds as $associationName => $associationData) {
            foreach ($associationData as $entityClass => $ids) {
                $result[$associationName][$entityClass] = \array_unique($ids);
            }
        }

        return $result;
    }

    /**
     * @param array                      $data
     * @param string                     $entityIdFieldName
     * @param CustomizeLoadedDataContext $context
     *
     * @return array
     */
    private function loadExpandedEntities(
        array $data,
        string $entityIdFieldName,
        CustomizeLoadedDataContext $context
    ): ?array {
        if (!$this->isExpandRequested($context)) {
            return null;
        }

        $ids = $this->getEntityIds($data, $entityIdFieldName);
        if (!$ids) {
            return null;
        }

        $expandedData = $this->loadExpandedEntityDataByIds(['' => $ids], $entityIdFieldName, $context);
        if (!isset($expandedData[''])) {
            return $data;
        }

        return $this->processCollection($data, $entityIdFieldName, $expandedData['']);
    }

    /**
     * @param array                         $data
     * @param string                        $entityIdFieldName
     * @param CustomizeLoadedDataContext    $context
     * @param EntityDefinitionFieldConfig[] $associations [association name => EntityDefinitionFieldConfig, ...]
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadExpandedEntitiesForAssociations(
        array $data,
        string $entityIdFieldName,
        CustomizeLoadedDataContext $context,
        array $associations
    ): ?array {
        if (!$associations) {
            return null;
        }

        $ids = $this->getEntityIdsForAssociations($data, $entityIdFieldName, $associations);
        if (!$ids) {
            return null;
        }

        $expandedData = $this->loadExpandedEntityDataByIds($ids, $entityIdFieldName, $context);
        foreach ($data as $key => $item) {
            foreach ($associations as $associationName => $associationConfig) {
                if (!isset($item[$associationName])) {
                    continue;
                }
                $associationData = $item[$associationName];
                if (!$associationData) {
                    continue;
                }
                if (!$associationConfig->isCollectionValuedAssociation()) {
                    $entityClass = $associationData[ConfigUtil::CLASS_NAME];
                    $entityId = $associationData[$entityIdFieldName];
                    if (isset($expandedData[$associationName][$entityClass][$entityId])) {
                        $data[$key][$associationName] = $expandedData[$associationName][$entityClass][$entityId];
                    }
                } elseif (isset($expandedData[$associationName])) {
                    $data[$key][$associationName] = $this->processCollection(
                        $associationData,
                        $entityIdFieldName,
                        $expandedData[$associationName]
                    );
                }
            }
        }

        return $data;
    }

    /**
     * @param array                      $ids [association name => [entity class => [entity id, ...], ...], ...]
     * @param string                     $entityIdFieldName
     * @param CustomizeLoadedDataContext $context
     *
     * @return array [association name => [entity class => [entity id => entity data, ...], ...], ...]
     */
    private function loadExpandedEntityDataByIds(
        array $ids,
        string $entityIdFieldName,
        CustomizeLoadedDataContext $context
    ): ?array {
        $result = [];
        $pathPrefix = $this->getPathPrefix($context);
        foreach ($ids as $associationName => $associationIds) {
            $associationPath = $pathPrefix
                ? $pathPrefix . $associationName
                : $associationName;
            foreach ($associationIds as $entityClass => $entityIds) {
                $expandedEntityData = $this->loadExpandedEntityData(
                    $entityClass,
                    $entityIds,
                    $entityIdFieldName,
                    $context,
                    $associationPath ?: null
                );
                if ($expandedEntityData) {
                    $result[$associationName][$entityClass] = $expandedEntityData;
                }
            }
        }

        return $result;
    }

    /**
     * @param string                     $entityClass
     * @param array                      $entityIds
     * @param string                     $entityIdFieldName
     * @param CustomizeLoadedDataContext $context
     * @param string|null                $associationPath
     *
     * @return array|null [entity id => entity data, ...]
     */
    private function loadExpandedEntityData(
        string $entityClass,
        array $entityIds,
        string $entityIdFieldName,
        CustomizeLoadedDataContext $context,
        ?string $associationPath
    ): ?array {
        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        if (!$this->resourcesProvider->isResourceAccessibleAsAssociation($entityClass, $version, $requestType)) {
            return null;
        }

        $configExtras = TargetConfigExtraBuilder::buildConfigExtras(
            $context->getConfigExtras(),
            $associationPath
        );
        $entityConfig = $this->configProvider
            ->getConfig($entityClass, $version, $requestType, $configExtras)
            ->getDefinition();
        if (null === $entityConfig) {
            return null;
        }

        $normalizationContext = $context->getNormalizationContext();
        if ($this->doctrineHelper->isManageableEntityClass($entityClass)) {
            $items = $this->loadExpandedDataForManageableEntities(
                $entityClass,
                $entityIds,
                $entityConfig,
                $normalizationContext
            );
        } else {
            $items = $this->loadExpandedDataForNotManageableEntities(
                $entityClass,
                $entityIds,
                $entityConfig,
                $normalizationContext
            );
        }

        $result = [];
        foreach ($items as $item) {
            if (!isset($item[ConfigUtil::CLASS_NAME])) {
                $item[ConfigUtil::CLASS_NAME] = $entityClass;
            }
            $result[$item[$entityIdFieldName]] = $item;
        }

        return $result;
    }

    /**
     * @param string                 $entityClass
     * @param array                  $entityIds
     * @param EntityDefinitionConfig $entityConfig
     * @param array                  $normalizationContext
     *
     * @return array [entity data, ...]
     */
    private function loadExpandedDataForManageableEntities(
        string $entityClass,
        array $entityIds,
        EntityDefinitionConfig $entityConfig,
        array $normalizationContext
    ): array {
        $qb = $this->doctrineHelper->createQueryBuilder($entityClass, 'e')
            ->where('e IN (:ids)')
            ->setParameter('ids', $entityIds);

        return $this->entitySerializer->serialize($qb, $entityConfig, $normalizationContext);
    }

    /**
     * @param string                 $entityClass
     * @param array                  $entityIds
     * @param EntityDefinitionConfig $entityConfig
     * @param array                  $normalizationContext
     *
     * @return array [entity data, ...]
     */
    private function loadExpandedDataForNotManageableEntities(
        string $entityClass,
        array $entityIds,
        EntityDefinitionConfig $entityConfig,
        array $normalizationContext
    ): array {
        $idFieldNames = $entityConfig->getIdentifierFieldNames();
        if (!$idFieldNames) {
            return [];
        }

        $items = [];
        $idFieldName = reset($idFieldNames);
        foreach ($entityIds as $entityId) {
            $items[] = $this->serializationHelper->postSerializeItem(
                [ConfigUtil::CLASS_NAME => $entityClass, $idFieldName => $entityId],
                $entityConfig,
                $normalizationContext
            );
        }

        return $this->serializationHelper->postSerializeCollection(
            $items,
            $entityConfig,
            $normalizationContext
        );
    }

    /**
     * @param array  $associationData
     * @param string $entityIdFieldName
     * @param array  $expandedEntities [entity class => [entity id => entity data, ...], ...]
     *
     * @return array
     */
    private function processCollection(
        array $associationData,
        string $entityIdFieldName,
        array $expandedEntities
    ): array {
        $result = [];
        foreach ($associationData as $key => $item) {
            $entityClass = $item[ConfigUtil::CLASS_NAME];
            $entityId = $item[$entityIdFieldName];
            if (isset($expandedEntities[$entityClass][$entityId])) {
                $item = $expandedEntities[$entityClass][$entityId];
            }
            $result[$key] = $item;
        }

        return $result;
    }
}
