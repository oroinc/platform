<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\AsyncOperation;

use Oro\Bundle\ApiBundle\Entity\AsyncOperation;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "summary" field for an asynchronous operation - sets the summary to NULL
 * if the asynchronous operation is not finished yet.
 */
class ComputeOperationSummary implements ProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        $summaryFieldName = $context->getResultFieldName('summary');
        if (!isset($data[$summaryFieldName]) || !$context->isFieldRequested($summaryFieldName)) {
            return;
        }

        $status = $data[$context->getResultFieldName('status')];
        if (AsyncOperation::STATUS_SUCCESS !== $status && AsyncOperation::STATUS_FAILED !== $status) {
            $data[$summaryFieldName] = null;
            $context->setData($data);
        }
    }
}
