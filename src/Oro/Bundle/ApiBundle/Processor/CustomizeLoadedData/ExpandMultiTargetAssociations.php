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

    #[\Override]
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
        if (!$associationPath) {
            /** @var EntityDefinitionConfigExtra|null $entityConfigExtra */
            $entityConfigExtra = $context->getConfigExtra(EntityDefinitionConfigExtra::NAME);
            if (ApiAction::GET_SUBRESOURCE === $entityConfigExtra?->getAction()) {
                return true;
            }
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

    private function getAssociationName(string $associationPath): string
    {
        $lastDelimiter = strrpos($associationPath, ConfigUtil::PATH_DELIMITER);
        if (false !== $lastDelimiter) {
            return substr($associationPath, $lastDelimiter + 1);
        }

        return $associationPath;
    }

    /**
     * @return array [association name => [is collection, id only], ...]
     */
    private function getAssociationsToExpand(
        EntityDefinitionConfig $config,
        CustomizeLoadedDataContext $context
    ): array {
        if (!$context->getPropertyPath()) {
            /** @var EntityDefinitionConfigExtra|null $entityConfigExtra */
            $entityConfigExtra = $context->getConfigExtra(EntityDefinitionConfigExtra::NAME);
            if (null !== $entityConfigExtra) {
                $targetAction = $entityConfigExtra->getAction();
                if (ApiAction::GET_RELATIONSHIP === $targetAction || ApiAction::GET_SUBRESOURCE === $targetAction) {
                    $associationName = $entityConfigExtra->getAssociationName();
                    $associationConfig = $config->getField($associationName);
                    if (null !== $associationConfig) {
                        $idOnly = $this->checkSpecialCaseAssociation($config, $associationConfig, $targetAction);
                        if (null !== $idOnly) {
                            return [$associationName => [$associationConfig->isCollectionValuedAssociation(), $idOnly]];
                        }
                    }
                }
            }
        }

        return $this->collectAssociationsToExpand($config, $context);
    }

    private function checkSpecialCaseAssociation(
        EntityDefinitionConfig $config,
        EntityDefinitionFieldConfig $associationConfig,
        string $targetAction
    ): ?bool {
        $dataType = $associationConfig->getDataType();
        if (DataType::isNestedAssociation($dataType)) {
            return ApiAction::GET_RELATIONSHIP === $targetAction;
        }
        if (ApiAction::GET_SUBRESOURCE === $targetAction && DataType::isExtendedAssociation($dataType)) {
            return false;
        }

        $targetClass = AssociationConfigUtil::getAssociationTargetClass($associationConfig, $config);
        if ($targetClass && is_a($targetClass, EntityIdentifier::class, true)) {
            return ApiAction::GET_RELATIONSHIP === $targetAction;
        }

        return null;
    }

    /**
     * @return array [association name => [is collection, id only], ...]
     */
    private function collectAssociationsToExpand(
        EntityDefinitionConfig $config,
        CustomizeLoadedDataContext $context
    ): array {
        $result = [];
        /** @var ExpandRelatedEntitiesConfigExtra|null $expandConfigExtra */
        $expandConfigExtra = $context->getConfigExtra(ExpandRelatedEntitiesConfigExtra::NAME);
        $pathPrefix = $this->getPathPrefix($context);
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            if ($field->isCollectionValuedAssociation() && !DataType::isExtendedAssociation($field->getDataType())) {
                continue;
            }
            $targetClass = AssociationConfigUtil::getAssociationTargetClass($field, $config);
            if (!$targetClass || !is_a($targetClass, EntityIdentifier::class, true)) {
                continue;
            }
            $fieldPath = $fieldName;
            if ($pathPrefix) {
                $fieldPath = $pathPrefix . $fieldPath;
            }
            $result[$fieldName] = [
                $field->isCollectionValuedAssociation(),
                null === $expandConfigExtra || !$expandConfigExtra->isExpandRequested($fieldPath)
            ];
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
     * @param array  $data
     * @param string $entityIdFieldName
     * @param array  $associations [association name => [is collection, id only], ...]
     *
     * @return array [association name => [entity class => [entity id, ...], ...], ...]
     */
    private function getEntityIdsForAssociations(array $data, string $entityIdFieldName, array $associations): array
    {
        $allIds = [];
        foreach ($data as $item) {
            foreach ($associations as $associationName => [$isCollection]) {
                if (!isset($item[$associationName])) {
                    continue;
                }
                $associationData = $item[$associationName];
                if (!$associationData) {
                    continue;
                }
                if ($isCollection) {
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
     * @param array                      $data
     * @param string                     $entityIdFieldName
     * @param CustomizeLoadedDataContext $context
     * @param array                      $associations [association name => [is collection, id only], ...]
     *
     * @return array|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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

        $isCollectionFlags = [];
        $idOnlyFlags = [];
        foreach ($associations as $associationName => [$isCollection, $idOnly]) {
            $isCollectionFlags[$associationName] = $isCollection;
            if ($idOnly) {
                $idOnlyFlags[$associationName] = true;
            }
        }
        $expandedData = $this->loadExpandedEntityDataByIds($ids, $entityIdFieldName, $context, $idOnlyFlags);
        foreach ($data as $key => $item) {
            foreach ($isCollectionFlags as $associationName => $isCollection) {
                if (!isset($item[$associationName])) {
                    continue;
                }
                $associationData = $item[$associationName];
                if (!$associationData) {
                    continue;
                }
                if (!isset($expandedData[$associationName])) {
                    continue;
                }
                $data[$key][$associationName] = $isCollection
                    ? $this->processCollection($associationData, $entityIdFieldName, $expandedData[$associationName])
                    : $this->processItem($associationData, $entityIdFieldName, $expandedData[$associationName]);
            }
        }

        return $data;
    }

    /**
     * @param array                      $ids [association name => [entity class => [entity id, ...], ...], ...]
     * @param string                     $entityIdFieldName
     * @param CustomizeLoadedDataContext $context
     * @param array                      $idOnlyFlags [association name => id only, ...]
     *
     * @return array|null [association name => [entity class => [entity id => entity data, ...], ...], ...]
     */
    private function loadExpandedEntityDataByIds(
        array $ids,
        string $entityIdFieldName,
        CustomizeLoadedDataContext $context,
        array $idOnlyFlags = []
    ): ?array {
        $result = [];
        $pathPrefix = $this->getPathPrefix($context);
        foreach ($ids as $associationName => $associationIds) {
            $associationPath = null;
            if ($associationName) {
                $associationPath = $pathPrefix
                    ? $pathPrefix . $associationName
                    : $associationName;
            }
            $idOnly = $idOnlyFlags[$associationName] ?? false;
            foreach ($associationIds as $entityClass => $entityIds) {
                $expandedEntityData = $this->loadExpandedEntityData(
                    $entityClass,
                    $entityIds,
                    $entityIdFieldName,
                    $context,
                    $associationPath,
                    $idOnly
                );
                if ($expandedEntityData) {
                    $result[$associationName][$entityClass] = $expandedEntityData;
                }
            }
        }

        return $result;
    }

    private function loadExpandedEntityData(
        string $entityClass,
        array $entityIds,
        string $entityIdFieldName,
        CustomizeLoadedDataContext $context,
        ?string $associationPath,
        bool $idOnly
    ): ?array {
        if ($idOnly) {
            return $this->dataLoader->loadExpandedEntityDataIdOnly(
                $entityClass,
                $entityIds,
                $entityIdFieldName,
                $context,
                $associationPath
            );
        }

        return $this->dataLoader->loadExpandedEntityData(
            $entityClass,
            $entityIds,
            $entityIdFieldName,
            $context,
            $associationPath
        );
    }

    /**
     * @param array  $associationData
     * @param string $entityIdFieldName
     * @param array  $expandedEntities [entity class => [entity id => entity data, ...], ...]
     *
     * @return array [item key => [name => value, ...], ...]
     */
    private function processCollection(
        array $associationData,
        string $entityIdFieldName,
        array $expandedEntities
    ): array {
        $result = [];
        foreach ($associationData as $key => $item) {
            $result[$key] = $this->processItem($item, $entityIdFieldName, $expandedEntities);
        }

        return $result;
    }

    /**
     * @param array  $associationData
     * @param string $entityIdFieldName
     * @param array  $expandedEntities [entity class => [entity id => entity data, ...], ...]
     *
     * @return array [name => value, ...]
     */
    private function processItem(
        array $associationData,
        string $entityIdFieldName,
        array $expandedEntities
    ): array {
        $entityClass = $associationData[ConfigUtil::CLASS_NAME];
        $entityId = $associationData[$entityIdFieldName];
        if (!isset($expandedEntities[$entityClass][$entityId])) {
            return $associationData;
        }

        $result = $expandedEntities[$entityClass][$entityId];
        foreach ($associationData as $key => $val) {
            if ($key !== ConfigUtil::CLASS_NAME && $key !== $entityIdFieldName && !\array_key_exists($key, $result)) {
                $result[$key] = $val;
            }
        }

        return $result;
    }
}
