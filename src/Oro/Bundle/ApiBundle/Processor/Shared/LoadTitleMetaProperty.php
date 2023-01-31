<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\AssociationConfigUtil;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\TargetConfigExtraBuilder;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
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

    private EntityTitleProvider $entityTitleProvider;
    private ExpandedAssociationExtractor $expandedAssociationExtractor;
    private ConfigProvider $configProvider;
    private ?string $titleFieldName = null;
    private ?ExpandRelatedEntitiesConfigExtra $expandConfigExtra = null;
    private ?Context $context = null;

    public function __construct(
        EntityTitleProvider $entityTitleProvider,
        ExpandedAssociationExtractor $expandedAssociationExtractor,
        ConfigProvider $configProvider
    ) {
        $this->entityTitleProvider = $entityTitleProvider;
        $this->expandedAssociationExtractor = $expandedAssociationExtractor;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
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
    }

    abstract protected function updateData(array $data, string $entityClass, EntityDefinitionConfig $config): array;

    protected function addTitles(array $data, string $entityClass, EntityDefinitionConfig $config): array
    {
        $idFieldName = AssociationConfigUtil::getEntityIdentifierFieldName($config);
        if ($idFieldName) {
            [$associationMap, $entityIdMap] = $this->getIdentifierMap(
                $data,
                $entityClass,
                $idFieldName,
                $config
            );
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
     * @param array                  $data
     * @param string                 $entityClass
     * @param string|string[]        $idFieldName
     * @param EntityDefinitionConfig $config
     *
     * @return array [
     *                  [data item key => [entity key, association map], ...],
     *                  [entity class => [id field name, [id, ...]], ...]
     *               ]
     */
    private function getIdentifierMap(
        array $data,
        string $entityClass,
        string|array $idFieldName,
        EntityDefinitionConfig $config
    ): array {
        // the COMPOSITE_KEYS element is internal and used as a temporary storage for
        // a string representations of composite keys
        // they are used to compare composite keys, rather that compare them as arrays
        // it is required because the identifier map should contains unique entity identifiers
        $entityIdMap = [self::COMPOSITE_KEYS => []];
        if (\is_array($idFieldName)) {
            $associationMap = $this->collectIdentifiersForCompositeId(
                $entityIdMap,
                $data,
                $entityClass,
                $idFieldName,
                $config
            );
        } else {
            $associationMap = $this->collectIdentifiers(
                $entityIdMap,
                $data,
                $entityClass,
                $idFieldName,
                $config
            );
        }
        unset($entityIdMap[self::COMPOSITE_KEYS]);

        return [$associationMap, $entityIdMap];
    }

    /**
     * @param array                  $entityIdMap [entity class => [id field name, [id, ...]], ...]
     * @param array                  $data
     * @param string                 $entityClass
     * @param string                 $idFieldName
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
        string $idFieldName,
        EntityDefinitionConfig $config,
        string $associationPath = null
    ): array {
        $associationMap = [];
        $isMultiTargetAssociation = is_a($entityClass, EntityIdentifier::class, true);
        $expandedAssociations = $this->getExpandedAssociations($config, $associationPath);
        $idPropertyPath = $this->getFieldPropertyPath($config, $idFieldName);
        foreach ($data as $itemKey => $item) {
            $itemExpandedAssociations = $expandedAssociations;
            if (isset($item[$idFieldName])) {
                $itemEntityClass = $item[ConfigUtil::CLASS_NAME] ?? $entityClass;
                if (!isset($entityIdMap[$itemEntityClass])) {
                    $entityIdMap[$itemEntityClass] = [$idPropertyPath, []];
                }
                $id = $item[$idFieldName];
                if (!\in_array($id, $entityIdMap[$itemEntityClass][1], true)) {
                    $entityIdMap[$itemEntityClass][1][] = $id;
                }
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

    /**
     * @param array                  $entityIdMap [entity class => [id field name, [id, ...]], ...]
     * @param array                  $data
     * @param string                 $entityClass
     * @param string[]               $idFieldName
     * @param EntityDefinitionConfig $config
     * @param string|null            $associationPath
     *
     * @return array [data item key => [entity key, association map], ...]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function collectIdentifiersForCompositeId(
        array &$entityIdMap,
        array $data,
        string $entityClass,
        array $idFieldName,
        EntityDefinitionConfig $config,
        string $associationPath = null
    ): array {
        $associationMap = [];
        $isMultiTargetAssociation = is_a($entityClass, EntityIdentifier::class, true);
        $expandedAssociations = $this->getExpandedAssociations($config, $associationPath);
        $idPropertyPath = $this->getFieldPropertyPaths($config, $idFieldName);
        foreach ($data as $itemKey => $item) {
            $itemExpandedAssociations = $expandedAssociations;
            if ($this->hasAllIdentifierFields($item, $idFieldName)) {
                $itemEntityClass = $item[ConfigUtil::CLASS_NAME] ?? $entityClass;
                if (!isset($entityIdMap[$itemEntityClass])) {
                    $entityIdMap[$itemEntityClass] = [$idPropertyPath, []];
                }
                if (!isset($entityIdMap[self::COMPOSITE_KEYS][$itemEntityClass])) {
                    $entityIdMap[self::COMPOSITE_KEYS][$itemEntityClass] = [];
                }
                $id = [];
                $idWithFieldNames = [];
                $key = [];
                foreach ($idFieldName as $fieldKey => $fieldName) {
                    $val = $item[$fieldName];
                    $id[] = $val;
                    $idWithFieldNames[$idPropertyPath[$fieldKey]] = $val;
                    $key[] = sprintf('%s=%s', $fieldName, $val);
                }
                $key = implode(',', $key);
                if (!\in_array($key, $entityIdMap[self::COMPOSITE_KEYS][$itemEntityClass], true)) {
                    $entityIdMap[$itemEntityClass][1][] = $id;
                    $entityIdMap[self::COMPOSITE_KEYS][$itemEntityClass][] = $key;
                }
                $associationMap[$itemKey] = [$this->buildEntityKey($itemEntityClass, $idWithFieldNames), null];
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
            $idFieldName = AssociationConfigUtil::getEntityIdentifierFieldName($config);
            if ($idFieldName) {
                $isCollection = $association->isCollectionValuedAssociation();
                if (!$isCollection) {
                    $value = [$value];
                }
                $targetEntityClass = $association->getTargetClass();
                $targetAssociationPath = $associationPath
                    ? $associationPath . ConfigUtil::PATH_DELIMITER . $associationName
                    : $associationName;
                if (\is_array($idFieldName)) {
                    $childAssociationMap = $this->collectIdentifiersForCompositeId(
                        $entityIdMap,
                        $value,
                        $targetEntityClass,
                        $idFieldName,
                        $config,
                        $targetAssociationPath
                    );
                } else {
                    $childAssociationMap = $this->collectIdentifiers(
                        $entityIdMap,
                        $value,
                        $targetEntityClass,
                        $idFieldName,
                        $config,
                        $targetAssociationPath
                    );
                }
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
     * @param EntityDefinitionConfig $config
     * @param string[]               $fieldNames
     *
     * @return string[]
     */
    private function getFieldPropertyPaths(EntityDefinitionConfig $config, array $fieldNames): array
    {
        $result = [];
        foreach ($fieldNames as $fieldName) {
            $result[] = $this->getFieldPropertyPath($config, $fieldName);
        }

        return $result;
    }

    private function getFieldPropertyPath(EntityDefinitionConfig $config, string $fieldName): string
    {
        $field = $config->findField($fieldName);
        if (null === $field) {
            return $fieldName;
        }

        return $field->getPropertyPath($fieldName);
    }

    /**
     * @param array    $item
     * @param string[] $idFieldNames
     *
     * @return bool
     */
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
        if (\is_array($entityId)) {
            $id = [];
            foreach ($entityId as $key => $val) {
                $id[] = sprintf('%s=%s', $key, $val);
            }
            $entityId = implode(';', $id);
        }

        return $entityClass . '::' . $entityId;
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string|null            $associationPath
     *
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
     * @param string      $entityClass
     * @param string|null $associationPath
     *
     * @return EntityDefinitionFieldConfig[]|null [association name => EntityDefinitionFieldConfig, ...]
     */
    private function getExpandedAssociationsByEntityClass(string $entityClass, ?string $associationPath): ?array
    {
        if (null === $this->expandConfigExtra) {
            return null;
        }

        $config = $this->configProvider
            ->getConfig(
                $entityClass,
                $this->context->getVersion(),
                $this->context->getRequestType(),
                TargetConfigExtraBuilder::buildConfigExtras($this->context->getConfigExtras(), $associationPath)
            )
            ->getDefinition();
        if (null === $config) {
            return null;
        }

        return $this->expandedAssociationExtractor->getExpandedAssociations(
            $config,
            $this->expandConfigExtra,
            $associationPath
        );
    }
}
