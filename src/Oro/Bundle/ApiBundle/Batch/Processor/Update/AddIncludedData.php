<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Batch\Model\IncludedData;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Adds the included data to each data record.
 */
class AddIncludedData implements ProcessorInterface
{
    public const OPERATION_NAME = 'append_included_data';

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        if ($context->isProcessed(self::OPERATION_NAME)) {
            // included data were already added
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

        $usedIncludedItems = [];
        $processedItemStatuses = $context->getProcessedItemStatuses();
        foreach ($data as $itemIndex => $item) {
            $includedItems = $this->getIncludedItems($item, $includedData, $usedIncludedItems);
            if (null === $includedItems) {
                $processedItemStatuses[$itemIndex] = BatchUpdateItemStatus::HAS_ERRORS;
            } elseif ($includedItems) {
                $includeAccessor = $includedData->getIncludeAccessor();
                foreach ($includedItems as $includedItemIndex => $includedItem) {
                    $usedIncludedItems[$includedItemIndex] = true;
                    [$type, $id] = $includeAccessor->getItemIdentifier($includedItem);
                    $includedItemSectionName = $includedData->getIncludedItemSectionName($type, $id);
                    $data[$itemIndex][$includedItemSectionName][] = $includedItem;
                }
            }
        }
        $context->setResult($data);
        $context->setProcessedItemStatuses($processedItemStatuses);
        $context->setProcessed(self::OPERATION_NAME);
    }

    /**
     * @param array        $item
     * @param IncludedData $includedData
     * @param array        $usedIncludedItems [included item index => true, ...]
     *
     * @return array|null [included item index => included item, ...]
     *                    or NULL if at least one included item exists in $usedIncludedItems
     */
    private function getIncludedItems(array $item, IncludedData $includedData, array $usedIncludedItems): ?array
    {
        $includeAccessor = $includedData->getIncludeAccessor();
        $relationships = $includeAccessor->getRelationships($includeAccessor->getPrimaryItemData($item));
        if (!$relationships) {
            return [];
        }

        $includedItems = $this->loadIncludedItems($relationships, [], $includedData, $usedIncludedItems);
        if ($includedItems) {
            ksort($includedItems, SORT_NUMERIC);
        }

        return $includedItems;
    }

    /**
     * @param array        $relationships          [item key => [type, id], ...]
     * @param array        $processedRelationships [item key => true, ...]
     * @param IncludedData $includedData
     * @param array        $usedIncludedItems      [included item index => true, ...]
     *
     * @return array|null [included item index => included item, ...]
     *                    or NULL if at least one included item exists in $usedIncludedItems
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function loadIncludedItems(
        array $relationships,
        array $processedRelationships,
        IncludedData $includedData,
        array $usedIncludedItems
    ): ?array {
        foreach ($relationships as $relationshipKey => $relationship) {
            $processedRelationships[$relationshipKey] = true;
        }

        $includedItems = [];
        $includedRelationships = [];
        $includeAccessor = $includedData->getIncludeAccessor();
        foreach ($relationships as [$type, $id]) {
            $includedItemIndex = $includedData->getIncludedItemIndex($type, $id);
            if (null === $includedItemIndex) {
                continue;
            }
            if (isset($usedIncludedItems[$includedItemIndex])) {
                $includedItems = null;
                break;
            }
            $includedItem = $includedData->getIncludedItem($includedItemIndex);
            $includedItems[$includedItemIndex] = $includedItem;
            $itemRelationships = $includeAccessor->getRelationships($includedItem);
            foreach ($itemRelationships as $itemRelationshipKey => $itemRelationship) {
                if (!isset($processedRelationships[$itemRelationshipKey])) {
                    $processedRelationships[$itemRelationshipKey] = true;
                    $includedRelationships[$itemRelationshipKey] = $itemRelationship;
                }
            }
        }
        if ($includedRelationships && null !== $includedItems) {
            $newIncludedItems = $this->loadIncludedItems(
                $includedRelationships,
                $processedRelationships,
                $includedData,
                $usedIncludedItems
            );
            if (null === $newIncludedItems) {
                $includedItems = null;
            } elseif ($newIncludedItems) {
                foreach ($newIncludedItems as $includedItemIndex => $includedItem) {
                    $includedItems[$includedItemIndex] = $includedItem;
                }
            }
        }

        return $includedItems;
    }
}
