<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Provider\EntityTitleProvider;
use Oro\Bundle\ApiBundle\Provider\ExpandedAssociationExtractor;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

abstract class LoadTitleMetaProperty implements ProcessorInterface
{
    const OPERATION_NAME = 'loadTitleMetaProperty';

    const TITLE_META_PROPERTY_NAME = 'title';

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
        $idFieldName = $this->getEntityIdentifierPropertyPath($config);
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
     * @param string                 $idFieldName
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
        foreach ($data as &$item) {
            $entityKey = $this->buildEntityKey($entityClass, $item[$idFieldName]);
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
            $idFieldName = $this->getEntityIdentifierPropertyPath($config);
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
     * @param string                 $idFieldName
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
     * @param string                 $idFieldName
     * @param EntityDefinitionConfig $config
     *
     * @return array [entity class => [id, ...], ...]
     */
    protected function getIdentifierMap(array $data, $entityClass, $idFieldName, EntityDefinitionConfig $config)
    {
        $map = [];
        $this->collectIdentifiers($map, $data, $entityClass, $idFieldName, $config);

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
        foreach ($data as $item) {
            if (array_key_exists($idFieldName, $item)) {
                if (!isset($map[$entityClass])) {
                    $map[$entityClass] = [];
                }

                $id = $item[$idFieldName];
                if (!in_array($id, $map[$entityClass], true)) {
                    $map[$entityClass][] = $id;
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
            $idFieldName = $this->getEntityIdentifierPropertyPath($config);
            if ($idFieldName) {
                if (!$association->isCollectionValuedAssociation()) {
                    $value = [$value];
                }
                $this->collectIdentifiers($map, $value, $association->getTargetClass(), $idFieldName, $config);
            }
        }
    }

    /**
     * @param EntityDefinitionConfig $config
     *
     * @return string|null
     */
    protected function getEntityIdentifierPropertyPath(EntityDefinitionConfig $config)
    {
        $fieldNames = $config->getIdentifierFieldNames();
        if (count($fieldNames) !== 1) {
            return null;
        }

        $fieldName = reset($fieldNames);
        $field = $config->findField($fieldName);
        if (null === $field) {
            return null;
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
        return $entityClass . '::' . $entityId;
    }
}
