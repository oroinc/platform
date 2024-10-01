<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Expands data for associated entities requested to be expanded.
 */
class ExpandAssociatedEntities implements ProcessorInterface
{
    private EntitySerializer $entitySerializer;
    private DoctrineHelper $doctrineHelper;

    public function __construct(EntitySerializer $entitySerializer, DoctrineHelper $doctrineHelper)
    {
        $this->entitySerializer = $entitySerializer;
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        /** @var EntityDefinitionConfig $config */
        $config = $context->getConfig();
        $expandedAssociationNames = $this->getExpandedAssociationNames($config);
        if (empty($expandedAssociationNames)) {
            return;
        }

        $data = $context->getData();
        $normalizationContext = $context->getNormalizationContext();
        foreach ($expandedAssociationNames as $fieldName) {
            /** @var EntityDefinitionFieldConfig $field */
            $field = $config->getField($fieldName);
            $targetIds = $this->getAssociationIds($data, $fieldName);
            if ($targetIds) {
                $associationData = $this->loadAssociationData(
                    $field->getTargetClass(),
                    $targetIds,
                    $field->getTargetEntity(),
                    $normalizationContext
                );
                $data = $this->applyAssociationData($data, $fieldName, $associationData);
            }
        }
        $context->setData($data);
    }

    /**
     * @return string[]
     */
    private function getExpandedAssociationNames(EntityDefinitionConfig $config): array
    {
        $expandedAssociationNames = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            $targetConfig = $field->getTargetEntity();
            if (null === $targetConfig || $field->isCollectionValuedAssociation()) {
                continue;
            }
            if (!$this->doctrineHelper->isManageableEntityClass($field->getTargetClass())
                && !ExtendHelper::isOutdatedEnumOptionEntity($field->getTargetClass())
            ) {
                continue;
            }
            if ($targetConfig->isIdentifierOnlyRequested()) {
                continue;
            }
            $expandedAssociationNames[] = $fieldName;
        }

        return $expandedAssociationNames;
    }

    private function getIdentifierFieldName(EntityDefinitionConfig $config): ?string
    {
        $idFieldNames = $config->getIdentifierFieldNames();
        if (\count($idFieldNames) === 1) {
            return reset($idFieldNames);
        }

        return null;
    }

    private function getAssociationIds(array $data, string $fieldName): array
    {
        $targetIds = [];
        foreach ($data as $item) {
            if (!isset($item[$fieldName])) {
                continue;
            }
            $targetIds[] = $item[$fieldName];
        }

        return array_unique($targetIds);
    }

    private function applyAssociationData(array $data, string $fieldName, array $associationData): array
    {
        foreach ($data as $key => $item) {
            if (!isset($item[$fieldName])) {
                continue;
            }
            $targetId = $item[$fieldName];
            if (isset($associationData[$targetId])) {
                $data[$key][$fieldName] = $associationData[$targetId];
            }
        }

        return $data;
    }

    /**
     * @return array [id => entity data, ...]
     */
    private function loadAssociationData(
        string $entityClass,
        array $ids,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): array {
        $idFieldName = $this->getIdentifierFieldName($config);
        if (!$idFieldName) {
            return [];
        }
        $isOutdatedEnumOptionEntity = ExtendHelper::isOutdatedEnumOptionEntity($entityClass);
        if ($isOutdatedEnumOptionEntity) {
            $enumCode = ExtendHelper::getEnumCode($entityClass);
            $ids = ExtendHelper::mapToEnumOptionIds($enumCode, $ids);
            $entityClass = EnumOption::class;
        }
        $idPropertyName = $config->getField($idFieldName)->getPropertyPath($idFieldName);
        $qb = $this->doctrineHelper
            ->createQueryBuilder($entityClass, 'e')
            ->where(sprintf('e.%s IN (:ids)', $idPropertyName))
            ->setParameter('ids', $ids);

        $rows = $this->entitySerializer->serialize($qb, $config, $normalizationContext);

        $result = [];
        foreach ($rows as $row) {
            if ($isOutdatedEnumOptionEntity) {
                $row[$idFieldName] = ExtendHelper::getEnumInternalId($row[$idFieldName]);
            }
            $result[$row[$idFieldName]] = $row;
        }

        return $result;
    }
}
