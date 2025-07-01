<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\AttachmentBundle\Api\MultiFileAssociationProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Computes values for multi files and multi images associations.
 */
class ComputeMultiFileAssociations implements ProcessorInterface
{
    private const string SORT_ORDER = 'sortOrder';

    public function __construct(
        private readonly MultiFileAssociationProvider $multiFileAssociationProvider,
        private readonly EntitySerializer $entitySerializer,
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $associationNames = $this->multiFileAssociationProvider->getMultiFileAssociationNames(
            $context->getClassName(),
            $context->getVersion(),
            $context->getRequestType()
        );
        if (!$associationNames) {
            return;
        }

        $config = $context->getConfig();

        $data = $context->getData();
        foreach ($associationNames as $associationName) {
            if (!$context->isFieldRequestedForCollection($associationName, $data)) {
                return;
            }
            $this->updateAssociationData($data, $associationName);
            $associationConfig = $config?->getField($associationName);
            if (null !== $associationConfig && !$associationConfig->isCollapsed()) {
                $this->expandAssociationData(
                    $data,
                    $associationName,
                    $associationConfig,
                    $context->getNormalizationContext()
                );
            }
        }
        $context->setData($data);
    }

    private function updateAssociationData(array &$data, string $associationName): void
    {
        foreach ($data as $key => $dataItem) {
            $associationItems = [];
            foreach ($dataItem['_' . $associationName] as $item) {
                $associationItem = $item['file'];
                $associationItem[self::SORT_ORDER] = $item[self::SORT_ORDER];
                $associationItems[] = $associationItem;
            }
            $data[$key][$associationName] = $associationItems;
        }
    }

    private function expandAssociationData(
        array &$data,
        string $associationName,
        EntityDefinitionFieldConfig $associationConfig,
        array $normalizationContext
    ): void {
        $ids = [];
        $associationEntityConfig = $associationConfig->getTargetEntity();
        $idFieldName = $this->getIdentifierFieldName($associationEntityConfig);
        foreach ($data as $dataItem) {
            foreach ($dataItem[$associationName] as $item) {
                $ids[] = $item[$idFieldName];
            }
        }
        if (!$ids) {
            return;
        }

        $associationData = $this->loadAssociationData(
            array_unique($ids),
            $associationConfig->getTargetClass(),
            $associationEntityConfig,
            $normalizationContext
        );
        foreach ($data as $key => $dataItem) {
            foreach ($dataItem[$associationName] as $itemKey => $item) {
                $itemData = $associationData[$item[$idFieldName]] ?? null;
                if (null !== $itemData) {
                    $itemData[self::SORT_ORDER] = $data[$key][$associationName][$itemKey][self::SORT_ORDER];
                    $data[$key][$associationName][$itemKey] = $itemData;
                }
            }
        }
    }

    private function loadAssociationData(
        array $ids,
        string $entityClass,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): array {
        $qb = $this->doctrineHelper
            ->createQueryBuilder($entityClass, 'e')
            ->where('e IN (:ids)')
            ->setParameter('ids', $ids);

        $rows = $this->entitySerializer->serialize($qb, $config, $normalizationContext);

        $result = [];
        $idFieldName = $this->getIdentifierFieldName($config);
        foreach ($rows as $row) {
            $result[$row[$idFieldName]] = $row;
        }

        return $result;
    }

    private function getIdentifierFieldName(EntityDefinitionConfig $config): string
    {
        $idFieldNames = $config->getIdentifierFieldNames();

        return reset($idFieldNames);
    }
}
