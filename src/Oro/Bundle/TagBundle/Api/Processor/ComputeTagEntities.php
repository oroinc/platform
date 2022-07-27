<?php

namespace Oro\Bundle\TagBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\Loader\MultiTargetAssociationDataLoader;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "entities" association for Tag entity.
 */
class ComputeTagEntities implements ProcessorInterface
{
    private const FIELD_NAME = 'entities';

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

        $data = $context->getData();

        if (!$context->isFieldRequestedForCollection(self::FIELD_NAME, $data)) {
            return;
        }

        $taggingFieldName = $context->getResultFieldName('tagging');
        foreach ($data as $key => $item) {
            $data[$key][self::FIELD_NAME] = $this->getEntities($item, $taggingFieldName);
        }

        /** @var ExpandRelatedEntitiesConfigExtra|null $expandConfigExtra */
        $expandConfigExtra = $context->getConfigExtra(ExpandRelatedEntitiesConfigExtra::NAME);
        if (null !== $expandConfigExtra && $expandConfigExtra->isExpandRequested(self::FIELD_NAME)) {
            $entityIdFieldName = $this->getIdentifierFieldName($context->getConfig());
            $ids = $this->getEntityIds($data, $entityIdFieldName);
            $expandedData = $this->loadExpandedEntityDataByIds($ids, $entityIdFieldName, $context);
            foreach ($data as $key => $item) {
                $associationData = $item[self::FIELD_NAME];
                foreach ($associationData as $dataIndex => $dataItem) {
                    $entityClass = $dataItem[ConfigUtil::CLASS_NAME];
                    $entityId = $dataItem[$entityIdFieldName];
                    if (isset($expandedData[$entityClass][$entityId])) {
                        $data[$key][self::FIELD_NAME][$dataIndex] = $expandedData[$entityClass][$entityId];
                    }
                }
            }
        }

        $context->setData($data);
    }

    private function getEntities(array $data, string $taggingFieldName): array
    {
        $entities = [];
        foreach ($data[$taggingFieldName] as $tagging) {
            $entity = $tagging['entity'];
            if (null !== $entity) {
                $entities[] = $entity;
            }
        }

        return $entities;
    }

    private function getIdentifierFieldName(EntityDefinitionConfig $config): string
    {
        $entityIdFieldNames = $config->getField(self::FIELD_NAME)
            ->getTargetEntity()
            ->getIdentifierFieldNames();

        return reset($entityIdFieldNames);
    }

    /**
     * @return array entity class => [entity id, ...], ...]
     */
    private function getEntityIds(array $data, string $entityIdFieldName): array
    {
        $entityIds = [];
        foreach ($data as $item) {
            $associationData = $item[self::FIELD_NAME];
            foreach ($associationData as $dataItem) {
                $entityIds[$dataItem[ConfigUtil::CLASS_NAME]][] = $dataItem[$entityIdFieldName];
            }
        }

        $result = [];
        foreach ($entityIds as $entityClass => $ids) {
            $result[$entityClass] = array_unique($ids);
        }

        return $result;
    }

    /**
     * @return array|null [entity class => [entity id => entity data, ...], ...]
     */
    private function loadExpandedEntityDataByIds(
        array $ids,
        string $entityIdFieldName,
        CustomizeLoadedDataContext $context
    ): ?array {
        $entityPropertyPath = $context->getPropertyPath();
        $associationPath = $entityPropertyPath
            ? $entityPropertyPath . ConfigUtil::PATH_DELIMITER . self::FIELD_NAME
            : self::FIELD_NAME;

        $result = [];
        foreach ($ids as $entityClass => $entityIds) {
            $expandedEntityData = $this->dataLoader->loadExpandedEntityData(
                $entityClass,
                $entityIds,
                $entityIdFieldName,
                $context,
                $associationPath
            );
            if ($expandedEntityData) {
                $result[$entityClass] = $expandedEntityData;
            }
        }

        return $result;
    }
}
