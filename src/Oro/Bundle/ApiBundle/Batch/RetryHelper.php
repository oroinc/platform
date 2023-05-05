<?php

namespace Oro\Bundle\ApiBundle\Batch;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItem;
use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;

/**
 * Provides a set of utility methods to simplify working batch operation items that should be processed again.
 */
class RetryHelper
{
    public function hasItemErrorsToSave(
        BatchUpdateItem $item,
        bool $hasItemsToRetry,
        array $processedItemStatuses
    ): bool {
        if (!$item->getContext()->hasErrors()) {
            return false;
        }

        return
            !$hasItemsToRetry
            || (
                isset($processedItemStatuses[$item->getIndex()])
                && BatchUpdateItemStatus::HAS_PERMANENT_ERRORS === $processedItemStatuses[$item->getIndex()]
            );
    }

    public function hasItemsToRetry(array $rawItems, array $processedItemStatuses): bool
    {
        $result = false;
        if (\count($rawItems) > 1) {
            foreach ($processedItemStatuses as $status) {
                if (BatchUpdateItemStatus::NOT_PROCESSED === $status
                    || BatchUpdateItemStatus::HAS_ERRORS === $status
                ) {
                    $result = true;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param array $rawItems
     * @param int[] $processedItemStatuses
     *
     * @return array [[first item index, [item, ...]], ...]
     */
    public function getChunksToRetry(array $rawItems, array $processedItemStatuses): array
    {
        $result = [];
        $lastNoErrorsItemIndex = -1;
        foreach ($processedItemStatuses as $index => $status) {
            if (BatchUpdateItemStatus::NOT_PROCESSED === $status) {
                if (-1 === $lastNoErrorsItemIndex
                    || \count($result[$lastNoErrorsItemIndex][1]) !== ($index - $result[$lastNoErrorsItemIndex][0])
                ) {
                    $lastNoErrorsItemIndex = \count($result);
                    $result[$lastNoErrorsItemIndex] = [$index, []];
                }
                $result[$lastNoErrorsItemIndex][1][] = $rawItems[$index];
            } elseif (BatchUpdateItemStatus::HAS_ERRORS === $status) {
                $result[] = [$index, [$rawItems[$index]]];
            }
        }

        return $result;
    }
}
