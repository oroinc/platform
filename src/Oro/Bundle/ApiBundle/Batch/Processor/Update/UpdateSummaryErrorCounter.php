<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\RetryHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates ErrorCount summary counter.
 */
class UpdateSummaryErrorCounter implements ProcessorInterface
{
    private RetryHelper $retryHelper;

    public function __construct(RetryHelper $retryHelper)
    {
        $this->retryHelper = $retryHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $summary = $context->getSummary();
        $summary->incrementErrorCount(\count($context->getErrors()));
        $items = $context->getBatchItems();
        if ($items) {
            $processedItemStatuses = $context->getProcessedItemStatuses() ?? [];
            $hasItemsToRetry = $this->retryHelper->hasItemsToRetry(
                $context->getResult() ?? [],
                $processedItemStatuses
            );
            foreach ($items as $item) {
                if ($this->retryHelper->hasItemErrorsToSave($item, $hasItemsToRetry, $processedItemStatuses)) {
                    $summary->incrementErrorCount(\count($item->getContext()->getErrors()));
                }
            }
        }
    }
}
