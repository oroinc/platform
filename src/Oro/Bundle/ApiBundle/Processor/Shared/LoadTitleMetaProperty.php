<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterIdentifierFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Config\TargetConfigExtraBuilder;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProviderInterface;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * The base class for processors that add "title" meta property value the result.
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class LoadTitleMetaProperty implements ProcessorInterface
{
    public const OPERATION_NAME = 'load_title_meta_property';

    public const TITLE_META_PROPERTY_NAME = 'title';

    /** Used for composite keys comparison */
    private const COMPOSITE_KEYS = 'composite_keys';

    private EntityTitleProviderInterface $entityTitleProvider;
    private ExpandedAssociationExtractor $expandedAssociationExtractor;
    private ConfigProvider $configProvider;
    private ?string $titleFieldName = null;
    private ?ExpandRelatedEntitiesConfigExtra $expandConfigExtra = null;
    private ?Context $context = null;

    public function __construct(
        EntityTitleProviderInterface $entityTitleProvider,
        ExpandedAssociationExtractor $expandedAssociationExtractor,
        ConfigProvider $configProvider
    ) {
        $this->entityTitleProvider = $entityTitleProvider;
        $this->expandedAssociationExtractor = $expandedAssociationExtractor;
        $this->configProvider = $configProvider;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the "title" meta property was already loaded
            return;
        }

        $data = $context->getResult();
        if (!\is_array($data) || empty($data)) {
            // empty or not supported result data
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // only configured API resources are supported
            return;
        }

        $titleFieldName = ConfigUtil::getPropertyPathOfMetaProperty(self::TITLE_META_PROPERTY_NAME, $config);
        if (!$titleFieldName) {
            // the "title" meta property was not requested
            return;
        }

        $entityClass = $context->getClassName();
        $parentResourceClass = $config->getParentResourceClass();
        if ($parentResourceClass) {
            $entityClass = $parentResourceClass;
        }

        $this->titleFieldName = $titleFieldName;
        $this->expandConfigExtra = $context->getConfigExtra(ExpandRelatedEntitiesConfigExtra::NAME);
        $this->context = $context;
        try {
            $context->setResult($this->updateData($data, $entityClass, $config));
        } finally {
            $this->titleFieldName = null;
            $this->expandConfigExtra = null;
            $this->context = null;
        }
        $context->setProcessed(self::OPERATION_NAME);
    }

    abstract protected function updateData(array $data, string $entityClass, EntityDefinitionConfig $config): array;

    protected function addTitles(array $data, string $entityClass, EntityDefinitionConfig $config): array
    {
        $idFieldNames = $config->getIdentifierFieldNames();
        if ($idFieldNames) {
            [$associationMap, $entityIdMap] = $this->getIdentifierMap($data, $entityClass, $idFieldNames, $config);
            if ($entityIdMap) {
                $titles = $this->getTitles($entityIdMap);
                if ($titles) {
                    $this->setTitles($data, $associationMap, $titles);
                }
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @param array $associationMap [data item key => [entity key, association map], ...]
     * @param array $titles         [entity key => entity title, ...]
     */
    private function setTitles(array &$data, array $associationMap, array $titles): void
    {
        foreach ($associationMap as $itemKey => [$entityKey, $childAssociationMap]) {
            if ($entityKey && \array_key_exists($entityKey, $titles)) {
                $data[$itemKey][$this->titleFieldName] = $titles[$entityKey];
            }
            if ($childAssociationMap && isset($data[$itemKey]) && \is_array($data[$itemKey])) {
                $this->setTitles($data[$itemKey], $childAssociationMap, $titles);
            }
        }
    }

    /**
     * @param array $entityIdMap [entity class => [id field name, [id, ...]], ...]
     *
     * @return array [entity key => entity title, ...]
     */
    private function getTitles(array $entityIdMap): array
    {
        $result = [];
        $rows = $this->entityTitleProvider->getTitles($entityIdMap);
        foreach ($rows as $row) {
            $entityKey = $this->buildEntityKey($row['entity'], $row['id']);
            $result[$entityKey] = $row['title'];
        }

        return $result;
    }

    /**
     * @return array [
     *                  [data item key => [entity key, association map], ...],
     *                  [entity class => [id field name, [id, ...]], ...]
     *               ]
     */
    private function getIdentifierMap(
        array $data,
        string $entityClass,
        array $idFieldNames,
        EntityDefinitionConfig $config
    ): array {
        // the COMPOSITE_KEYS element is internal and used as a temporary storage for
        // a string representations of composite keys
        // they are used to compare composite keys, rather that compare them as arrays
        // it is required because the identifier map should contains unique entity identifiers
        $entityIdMap = [self::COMPOSITE_KEYS => []];
        $associationMap = $this->collectIdentifiers($entityIdMap, $data, $entityClass, $idFieldNames, $config);
        unset($entityIdMap[self::COMPOSITE_KEYS]);

        return [$associationMap, $entityIdMap];
    }

    /**
     * @param array                  $entityIdMap [entity class => [id field name, [id, ...]], ...]
     * @param array                  $data
     * @param string                 $entityClass
     * @param string[]               $idFieldNames
     * @param EntityDefinitionConfig $config
     * @param string|null            $associationPath
     *
     * @return array [data item key => [entity key, association map], ...]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function collectIdentifiers(
        array &$entityIdMap,
        array $data,
        string $entityClass,
        array $idFieldNames,
        EntityDefinitionConfig $config,
        ?string $associationPath = null
    ): array {
        $associationMap = [];
        $isMultiTargetAssociation = is_a($entityClass, EntityIdentifier::class, true);
        $expandedAssociations = $this->getExpandedAssociations($config, $associationPath);
        $idPropertyPaths = $this->getFieldPropertyPaths($config, $idFieldNames);
        foreach ($data as $itemKey => $item) {
            $itemExpandedAssociations = $expandedAssociations;
            $itemEntityClass = $item[ConfigUtil::CLASS_NAME] ?? $entityClass;
            if ($isMultiTargetAssociation && !is_a($itemEntityClass, EntityIdentifier::class, true)) {
                $itemIdPropertyPaths = $this->getMultiTargetAssociationIdPropertyPaths($itemEntityClass);
                if (null !== $itemIdPropertyPaths) {
                    $idPropertyPaths = $itemIdPropertyPaths;
                    $idFieldNames = array_keys($idPropertyPaths);
                }
            }
            if ($this->hasAllIdentifierFields($item, $idFieldNames)) {
                $id = $this->addIdentifierToEntityIdMap($entityIdMap, $item, $itemEntityClass, $idPropertyPaths);
                $associationMap[$itemKey] = [$this->buildEntityKey($itemEntityClass, $id), null];
                if ($isMultiTargetAssociation && !$itemExpandedAssociations) {
                    $itemExpandedAssociations = $this->getExpandedAssociationsByEntityClass(
                        $itemEntityClass,
                        $associationPath
                    );
                }
            }
            if ($itemExpandedAssociations) {
                $childAssociationMap = $this->collectIdentifiersForAssociations(
                    $entityIdMap,
                    $item,
                    $itemExpandedAssociations,
                    $associationPath
                );
                if ($childAssociationMap) {
                    if (isset($associationMap[$itemKey])) {
                        $associationMap[$itemKey][1] = $childAssociationMap;
                    } else {
                        $associationMap[$itemKey] = [null, $childAssociationMap];
                    }
                }
            }
        }

        return $associationMap;
    }

    private function addIdentifierToEntityIdMap(
        array &$entityIdMap,
        array $item,
        string $itemEntityClass,
        array $idPropertyPaths
    ): mixed {
        if (\count($idPropertyPaths) === 1) {
            return $this->addSingleIdentifierToEntityIdMap(
                $entityIdMap,
                $item,
                $itemEntityClass,
                array_key_first($idPropertyPaths),
                reset($idPropertyPaths)
            );
        }

        return $this->addCompositeIdentifierToEntityIdMap(
            $entityIdMap,
            $item,
            $itemEntityClass,
            $idPropertyPaths
        );
    }

    private function addSingleIdentifierToEntityIdMap(
        array &$entityIdMap,
        array $item,
        string $itemEntityClass,
        string $idFieldName,
        string $idPropertyPath
    ): mixed {
        if (!isset($entityIdMap[$itemEntityClass])) {
            $entityIdMap[$itemEntityClass] = [$idPropertyPath, []];
        }
        $id = $item[$idFieldName];
        if (!\in_array($id, $entityIdMap[$itemEntityClass][1], true)) {
            $entityIdMap[$itemEntityClass][1][] = $id;
        }

        return $id;
    }

    private function addCompositeIdentifierToEntityIdMap(
        array &$entityIdMap,
        array $item,
        string $itemEntityClass,
        array $idPropertyPaths
    ): mixed {
        if (!isset($entityIdMap[$itemEntityClass])) {
            $entityIdMap[$itemEntityClass] = [array_values($idPropertyPaths), []];
        }
        if (!isset($entityIdMap[self::COMPOSITE_KEYS][$itemEntityClass])) {
            $entityIdMap[self::COMPOSITE_KEYS][$itemEntityClass] = [];
        }
        $id = [];
        $idWithFieldNames = [];
        $itemKeyParts = [];
        foreach ($idPropertyPaths as $fieldName => $propertyPath) {
            $val = $item[$fieldName];
            $id[] = $val;
            $idWithFieldNames[$propertyPath] = $val;
            $itemKeyParts[] = \sprintf('%s=%s', $fieldName, $val);
        }
        $itemKey = implode(',', $itemKeyParts);
        if (!\in_array($itemKey, $entityIdMap[self::COMPOSITE_KEYS][$itemEntityClass], true)) {
            $entityIdMap[$itemEntityClass][1][] = $id;
            $entityIdMap[self::COMPOSITE_KEYS][$itemEntityClass][] = $itemKey;
        }

        return $idWithFieldNames;
    }

    /**
     * @param array                         $entityIdMap [entity class => [id field name, [id, ...]], ...]
     * @param array                         $item
     * @param EntityDefinitionFieldConfig[] $expandedAssociations
     * @param string|null                   $associationPath
     *
     * @return array [data item key => [entity key, association map], ...]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function collectIdentifiersForAssociations(
        array &$entityIdMap,
        array $item,
        array $expandedAssociations,
        ?string $associationPath
    ): array {
        $associationMap = [];
        foreach ($expandedAssociations as $associationName => $association) {
            if (!\array_key_exists($associationName, $item)) {
                continue;
            }
            $value = $item[$associationName];
            if (!\is_array($value) || empty($value)) {
                continue;
            }

            $config = $association->getTargetEntity();
            $idFieldNames = $config->getIdentifierFieldNames();
            if ($idFieldNames && $config->isMetaPropertyEnabled('title')) {
                $isCollection = $association->isCollectionValuedAssociation();
                if (!$isCollection) {
                    $value = [$value];
                }
                $targetEntityClass = $association->getTargetClass();
                if (ExtendHelper::isOutdatedEnumOptionEntity($targetEntityClass)) {
                    $targetEntityClass = EnumOption::class;
                }
                $targetAssociationPath = $associationPath
                    ? $associationPath . ConfigUtil::PATH_DELIMITER . $associationName
                    : $associationName;
                $childAssociationMap = $this->collectIdentifiers(
                    $entityIdMap,
                    $value,
                    $targetEntityClass,
                    $idFieldNames,
                    $config,
                    $targetAssociationPath
                );
                if ($childAssociationMap) {
                    $associationMap[$associationName] = $isCollection
                        ? [null, $childAssociationMap]
                        : reset($childAssociationMap);
                }
            }
        }

        return $associationMap;
    }

    /**
     * @return string[] [field name => property path, ...]
     */
    private function getFieldPropertyPaths(EntityDefinitionConfig $config, array $fieldNames): array
    {
        $result = [];
        foreach ($fieldNames as $fieldName) {
            $field = $config->findField($fieldName);
            $result[$fieldName] = null !== $field
                ? $field->getPropertyPath($fieldName)
                : $fieldName;
        }

        return $result;
    }

    /**
     * @return string[]|null [field name => property path, ...]
     */
    private function getMultiTargetAssociationIdPropertyPaths(string $entityClass): ?array
    {
        $config = $this->getConfig(
            $entityClass,
            [new EntityDefinitionConfigExtra($this->context->getAction()), new FilterIdentifierFieldsConfigExtra()]
        );
        if (null === $config) {
            return null;
        }

        return $this->getFieldPropertyPaths($config, $config->getIdentifierFieldNames());
    }

    private function hasAllIdentifierFields(array $item, array $idFieldNames): bool
    {
        foreach ($idFieldNames as $fieldName) {
            if (!\array_key_exists($fieldName, $item)) {
                return false;
            }
        }

        return true;
    }

    private function buildEntityKey(string $entityClass, mixed $entityId): string
    {
        if (!\is_array($entityId)) {
            return $entityClass . '::' . $entityId;
        }

        if (\count($entityId) === 1) {
            return $entityClass . '::' . reset($entityId);
        }

        $id = [];
        foreach ($entityId as $key => $val) {
            $id[] = \sprintf('%s=%s', $key, $val);
        }

        return $entityClass . '::' . implode(';', $id);
    }

    /**
     * @return EntityDefinitionFieldConfig[]|null [association name => EntityDefinitionFieldConfig, ...]
     */
    private function getExpandedAssociations(EntityDefinitionConfig $config, ?string $associationPath): ?array
    {
        if (null === $this->expandConfigExtra) {
            return null;
        }

        return $this->expandedAssociationExtractor->getExpandedAssociations(
            $config,
            $this->expandConfigExtra,
            $associationPath
        );
    }

    /**
     * @return EntityDefinitionFieldConfig[]|null [association name => EntityDefinitionFieldConfig, ...]
     */
    private function getExpandedAssociationsByEntityClass(string $entityClass, ?string $associationPath): ?array
    {
        if (null === $this->expandConfigExtra) {
            return null;
        }

        $config = $this->getConfig(
            $entityClass,
            TargetConfigExtraBuilder::buildConfigExtras($this->context->getConfigExtras(), $associationPath)
        );
        if (null === $config) {
            return null;
        }

        return $this->expandedAssociationExtractor->getExpandedAssociations(
            $config,
            $this->expandConfigExtra,
            $associationPath
        );
    }

    private function getConfig(string $entityClass, array $extras): ?EntityDefinitionConfig
    {
        return $this->configProvider
            ->getConfig($entityClass, $this->context->getVersion(), $this->context->getRequestType(), $extras)
            ->getDefinition();
    }
}
