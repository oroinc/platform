<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
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
    public const OPERATION_NAME = 'loadTitleMetaProperty';

    public const TITLE_META_PROPERTY_NAME = 'title';

    /** Used for composite keys comparison */
    private const COMPOSITE_KEYS = 'composite_keys';

    /** @var EntityTitleProvider */
    protected $entityTitleProvider;

    /** @var ExpandedAssociationExtractor */
    protected $expandedAssociationExtractor;

    /**
     * @param EntityTitleProvider          $entityTitleProvider
     * @param ExpandedAssociationExtractor $expandedAssociationExtractor
     */
    public function __construct(
        EntityTitleProvider $entityTitleProvider,
        ExpandedAssociationExtractor $expandedAssociationExtractor
    ) {
        $this->entityTitleProvider = $entityTitleProvider;
        $this->expandedAssociationExtractor = $expandedAssociationExtractor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // the "title" meta property was already loaded
            return;
        }

        $data = $context->getResult();
        if (!is_array($data) || empty($data)) {
            // empty or not supported result data
            return;
        }

        $config = $context->getConfig();
        if (null === $config) {
            // only configured API resources are supported
            return;
        }

        $titlePropertyPath = ConfigUtil::getPropertyPathOfMetaProperty(self::TITLE_META_PROPERTY_NAME, $config);
        if (!$titlePropertyPath) {
            // the "title" meta property was not requested
            return;
        }

        $context->setResult(
            $this->updateData($data, $context->getClassName(), $config, $titlePropertyPath)
        );
    }

    /**
     * @param array                  $data
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     * @param string                 $titleFieldName
     *
     * @return array
     */
    abstract protected function updateData(
        array $data,
        $entityClass,
        EntityDefinitionConfig $config,
        $titleFieldName
    );

    /**
     * @param array                  $data
     * @param string                 $entityClass
     * @param EntityDefinitionConfig $config
     * @param string                 $titleFieldName
     *
     * @return array
     */
    protected function addTitles(
        array $data,
        $entityClass,
        EntityDefinitionConfig $config,
        $titleFieldName
    ) {
        $idFieldName = $this->getEntityIdentifierFieldName($config);
        if ($idFieldName) {
            $parentResourceClass = $config->getParentResourceClass();
            if ($parentResourceClass) {
                $entityClass = $parentResourceClass;
            }
            $titles = $this->getTitles($data, $entityClass, $idFieldName, $config);
            $this->setTitles($data, $entityClass, $idFieldName, $config, $titles, $titleFieldName);
        }

        return $data;
    }

    /**
     * @param array                  $data
     * @param string                 $entityClass
     * @param string|string[]        $idFieldName
     * @param EntityDefinitionConfig $config
     * @param array                  $titles
     * @param string                 $titleFieldName
     */
    protected function setTitles(
        array &$data,
        $entityClass,
        $idFieldName,
        EntityDefinitionConfig $config,
        array $titles,
        $titleFieldName
    ) {
        $expandedAssociations = $this->expandedAssociationExtractor->getExpandedAssociations($config);
        if (is_array($idFieldName)) {
            $idMap = [];
            foreach ($idFieldName as $fieldName) {
                $idMap[$fieldName] = $config->getField($fieldName)->getPropertyPath($fieldName);
            }
            foreach ($data as &$item) {
                $entityId = [];
                foreach ($idMap as $fieldName => $propertyPath) {
                    if (array_key_exists($fieldName, $item)) {
                        $entityId[$propertyPath] = $item[$fieldName];
                    }
                }
                $entityKey = $this->buildEntityKey($entityClass, $entityId);
                if (array_key_exists($entityKey, $titles)) {
                    $item[$titleFieldName] = $titles[$entityKey];
                }
                if (!empty($expandedAssociations)) {
                    $this->setTitlesForAssociations(
                        $item,
                        $expandedAssociations,
                        $titles,
                        $titleFieldName
                    );
                }
            }
        } else {
            foreach ($data as &$item) {
                if (isset($item[$idFieldName])) {
                    $entityKey = $this->buildEntityKey($entityClass, $item[$idFieldName]);
                    if (array_key_exists($entityKey, $titles)) {
                        $item[$titleFieldName] = $titles[$entityKey];
                    }
                }
                if (!empty($expandedAssociations)) {
                    $this->setTitlesForAssociations(
                        $item,
                        $expandedAssociations,
                        $titles,
                        $titleFieldName
                    );
                }
            }
        }
    }

    /**
     * @param array                         $data
     * @param EntityDefinitionFieldConfig[] $expandedAssociations
     * @param array                         $titles
     * @param string                        $titleFieldName
     */
    protected function setTitlesForAssociations(
        array &$data,
        array $expandedAssociations,
        array $titles,
        $titleFieldName
    ) {
        foreach ($expandedAssociations as $associationName => $association) {
            if (!array_key_exists($associationName, $data)) {
                continue;
            }
            $value = &$data[$associationName];
            if (!is_array($value) || empty($value)) {
                continue;
            }

            $config = $association->getTargetEntity();
            $idFieldName = $this->getEntityIdentifierFieldName($config);
            if ($idFieldName) {
                $entityClass = $association->getTargetClass();
                if ($association->isCollectionValuedAssociation()) {
                    $this->setTitles($value, $entityClass, $idFieldName, $config, $titles, $titleFieldName);
                } else {
                    $collection = [$value];
                    $this->setTitles($collection, $entityClass, $idFieldName, $config, $titles, $titleFieldName);
                    $value = reset($collection);
                }
            }
        }
    }

    /**
     * @param array                  $data
     * @param string                 $entityClass
     * @param string|string[]        $idFieldName
     * @param EntityDefinitionConfig $config
     *
     * @return array [entity key => entity title, ...]
     */
    protected function getTitles(array $data, $entityClass, $idFieldName, EntityDefinitionConfig $config)
    {
        $result = [];
        $identifierMap = $this->getIdentifierMap($data, $entityClass, $idFieldName, $config);
        if (!empty($identifierMap)) {
            $rows = $this->entityTitleProvider->getTitles($identifierMap);
            foreach ($rows as $row) {
                $entityKey = $this->buildEntityKey($row['entity'], $row['id']);
                $result[$entityKey] = $row['title'];
            }
        }

        return $result;
    }

    /**
     * @param array                  $data
     * @param string                 $entityClass
     * @param string|string[]        $idFieldName
     * @param EntityDefinitionConfig $config
     *
     * @return array [entity class => [entity id field name, [entity id, ...]], ...]
     */
    protected function getIdentifierMap(array $data, $entityClass, $idFieldName, EntityDefinitionConfig $config)
    {
        // the COMPOSITE_KEYS element is internal and used as a temporary storage for
        // a string representations of composite keys
        // they are used to compare composite keys, rather that compare them as arrays
        // it is required because the identifier map should contains unique entity identifiers
        $map = [self::COMPOSITE_KEYS => []];
        if (is_array($idFieldName)) {
            $this->collectIdentifiersForCompositeId($map, $data, $entityClass, $idFieldName, $config);
        } else {
            $this->collectIdentifiers($map, $data, $entityClass, $idFieldName, $config);
        }
        unset($map[self::COMPOSITE_KEYS]);

        return $map;
    }

    /**
     * @param array                  $map
     * @param array                  $data
     * @param string                 $entityClass
     * @param string                 $idFieldName
     * @param EntityDefinitionConfig $config
     */
    protected function collectIdentifiers(
        array &$map,
        array $data,
        $entityClass,
        $idFieldName,
        EntityDefinitionConfig $config
    ) {
        $expandedAssociations = $this->expandedAssociationExtractor->getExpandedAssociations($config);
        $idPropertyPath = $this->getFieldPropertyPath($config, $idFieldName);
        foreach ($data as $item) {
            if (isset($item[$idFieldName])) {
                if (!isset($map[$entityClass])) {
                    $map[$entityClass] = [$idPropertyPath, []];
                }
                $id = $item[$idFieldName];
                if (!in_array($id, $map[$entityClass][1], true)) {
                    $map[$entityClass][1][] = $id;
                }
            }
            if (!empty($expandedAssociations)) {
                $this->collectIdentifiersForAssociations($map, $item, $expandedAssociations);
            }
        }
    }

    /**
     * @param array                  $map
     * @param array                  $data
     * @param string                 $entityClass
     * @param string[]               $idFieldName
     * @param EntityDefinitionConfig $config
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function collectIdentifiersForCompositeId(
        array &$map,
        array $data,
        $entityClass,
        $idFieldName,
        EntityDefinitionConfig $config
    ) {
        $expandedAssociations = $this->expandedAssociationExtractor->getExpandedAssociations($config);
        $idPropertyPath = [];
        foreach ($idFieldName as $fieldName) {
            $idPropertyPath[] = $this->getFieldPropertyPath($config, $fieldName);
        }
        foreach ($data as $item) {
            $hasId = true;
            foreach ($idFieldName as $fieldName) {
                if (!array_key_exists($fieldName, $item)) {
                    $hasId = false;
                    break;
                }
            }
            if ($hasId) {
                if (!isset($map[$entityClass])) {
                    $map[$entityClass] = [$idPropertyPath, []];
                }
                if (!isset($map[self::COMPOSITE_KEYS][$entityClass])) {
                    $map[self::COMPOSITE_KEYS][$entityClass] = [];
                }
                $id = [];
                $key = [];
                foreach ($idFieldName as $fieldName) {
                    $val = $item[$fieldName];
                    $id[] = $val;
                    $key[] = sprintf('%s=%s', $fieldName, $val);
                }
                $key = implode(',', $key);
                if (!in_array($key, $map[self::COMPOSITE_KEYS][$entityClass], true)) {
                    $map[$entityClass][1][] = $id;
                    $map[self::COMPOSITE_KEYS][$entityClass][] = $key;
                }
            }
            if (!empty($expandedAssociations)) {
                $this->collectIdentifiersForAssociations($map, $item, $expandedAssociations);
            }
        }
    }

    /**
     * @param array                         $map
     * @param array                         $item
     * @param EntityDefinitionFieldConfig[] $expandedAssociations
     */
    protected function collectIdentifiersForAssociations(array &$map, array $item, array $expandedAssociations)
    {
        foreach ($expandedAssociations as $associationName => $association) {
            if (!array_key_exists($associationName, $item)) {
                continue;
            }
            $value = $item[$associationName];
            if (!is_array($value) || empty($value)) {
                continue;
            }

            $config = $association->getTargetEntity();
            $idFieldName = $this->getEntityIdentifierFieldName($config);
            if ($idFieldName) {
                if (!$association->isCollectionValuedAssociation()) {
                    $value = [$value];
                }
                $targetEntityClass = $association->getTargetClass();
                if (is_array($idFieldName)) {
                    $this->collectIdentifiersForCompositeId($map, $value, $targetEntityClass, $idFieldName, $config);
                } else {
                    $this->collectIdentifiers($map, $value, $targetEntityClass, $idFieldName, $config);
                }
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $config
     *
     * @return string|string[]|null
     */
    protected function getEntityIdentifierFieldName(EntityDefinitionConfig $config)
    {
        $fieldNames = $config->getIdentifierFieldNames();
        $numberOfFields = count($fieldNames);
        if (0 === $numberOfFields) {
            return null;
        }
        if (1 === $numberOfFields) {
            return reset($fieldNames);
        }

        return $fieldNames;
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string                 $fieldName
     *
     * @return string
     */
    protected function getFieldPropertyPath(EntityDefinitionConfig $config, $fieldName)
    {
        $field = $config->findField($fieldName);
        if (null === $field) {
            return $fieldName;
        }

        return $field->getPropertyPath($fieldName);
    }

    /**
     * @param string $entityClass
     * @param mixed  $entityId
     *
     * @return string
     */
    protected function buildEntityKey($entityClass, $entityId)
    {
        if (is_array($entityId)) {
            $id = [];
            foreach ($entityId as $key => $val) {
                $id[] = sprintf('%s=%s', $key, $val);
            }
            $entityId = implode(';', $id);
        }

        return $entityClass . '::' . $entityId;
    }
}
