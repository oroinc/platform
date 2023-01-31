<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates the following summary counters:
 * * WriteCount
 * * CreateCount
 * * UpdateCount
 */
class UpdateSummaryCounters implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $items = $context->getBatchItems();
        if (!$items) {
            return;
        }

        $summary = $context->getSummary();
        $processedItemStatuses = $context->getProcessedItemStatuses();
        foreach ($items as $item) {
            if (BatchUpdateItemStatus::NO_ERRORS === $processedItemStatuses[$item->getIndex()]) {
                switch ($item->getContext()->getTargetAction()) {
                    case ApiAction::CREATE:
                        $summary->incrementWriteCount();
                        $summary->incrementCreateCount();
                        break;
                    case ApiAction::UPDATE:
                        $summary->incrementWriteCount();
                        $summary->incrementUpdateCount();
                        break;
                }
            }
        }
    }
}
