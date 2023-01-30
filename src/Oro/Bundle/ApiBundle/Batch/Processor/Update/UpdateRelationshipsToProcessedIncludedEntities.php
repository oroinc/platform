<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates relationships to already processed included items.
 */
class UpdateRelationshipsToProcessedIncludedEntities implements ProcessorInterface
{
    public const OPERATION_NAME = 'update_relationships_to_processed_included_entities';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // relationships were already updated
            return;
        }

        $includedData = $context->getIncludedData();
        if (null === $includedData) {
            // included data were not loaded
            return;
        }

        $data = $context->getResult();
        if (null === $data) {
            // data were not loaded
            return;
        }

        if ($includedData->hasProcessedIncludedItems()) {
            $includedSectionNames = $includedData->getAllSectionNames();
            $includeAccessor = $includedData->getIncludeAccessor();
            $processedItemStatuses = $context->getProcessedItemStatuses();
            foreach ($data as $itemIndex => &$item) {
                if (BatchUpdateItemStatus::NOT_PROCESSED === $processedItemStatuses[$itemIndex]) {
                    $itemData = $includeAccessor->getPrimaryItemData($item);
                    $this->updateRelationships($itemData, $includedData);
                    $includeAccessor->setPrimaryItemData($item, $itemData);
                    foreach ($includedSectionNames as $includedSectionName) {
                        if (isset($item[$includedSectionName])) {
                            foreach ($item[$includedSectionName] as $includedIndex => &$includedItem) {
                                $this->updateRelationships($includedItem, $includedData);
                            }
                            unset($includedItem);
                        }
                    }
                }
            }
            unset($item);
            $context->setResult($data);
        }
        $context->setProcessed(self::OPERATION_NAME);
    }

    private function updateRelationships(array &$item, IncludedData $includedData): void
    {
        $includeAccessor = $includedData->getIncludeAccessor();
        $includeAccessor->updateRelationships(
            $item,
            function (string $type, string $id) use ($includedData) {
                return $includedData->getProcessedIncludedItemId($type, $id);
            }
        );
    }
}
