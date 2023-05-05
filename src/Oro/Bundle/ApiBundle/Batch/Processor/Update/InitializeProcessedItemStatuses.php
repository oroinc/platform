<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update;

use Oro\Bundle\ApiBundle\Batch\Handler\BatchUpdateItemStatus;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets the initial value for the processing result statuses of batch items.
 */
class InitializeProcessedItemStatuses implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var BatchUpdateContext $context */

        $processedItemStatuses = $context->getProcessedItemStatuses();
        if (null === $processedItemStatuses) {
            $records = $context->getResult();
            if (null !== $records) {
                $processedItemStatuses = [];
                foreach ($records as $index => $record) {
                    $processedItemStatuses[$index] = BatchUpdateItemStatus::NOT_PROCESSED;
                }
                $context->setProcessedItemStatuses($processedItemStatuses);
            }
        }
    }
}
