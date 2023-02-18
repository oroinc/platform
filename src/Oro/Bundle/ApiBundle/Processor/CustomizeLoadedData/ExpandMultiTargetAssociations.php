<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\AssociationConfigUtil;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Loader\MultiTargetAssociationDataLoader;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks if expanding of a multi-target association was requested,
 * and if so, load data for it.
 * @see \Oro\Bundle\ApiBundle\Metadata\TargetMetadataAccessor
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExpandMultiTargetAssociations implements ProcessorInterface
{
    private MultiTargetAssociationDataLoader $dataLoader;

    public function __construct(MultiTargetAssociationDataLoader $dataLoader)
    {
        $this->dataLoader = $dataLoader;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
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

        if (is_a($context->getClassName(), EntityIdentifier::class, true)) {
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
                    if ($targetClass && is_a($targetClass, EntityIdentifier::class, true)) {
                        return [$subresourceAssociationName => $associationConfig];
                    }
                }
            }
        }

        return $this->collectAssociationsToExpand($config, $context);
    }

    /**
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
                if (!$targetClass || !is_a($targetClass, EntityIdentifier::class, true)) {
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
                $result[$associationName][$entityClass] = array_unique($ids);
            }
        }

        return $result;
    }

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
     * @return array|null
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
     * @return array|null [association name => [entity class => [entity id => entity data, ...], ...], ...]
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
                $expandedEntityData = $this->dataLoader->loadExpandedEntityData(
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
