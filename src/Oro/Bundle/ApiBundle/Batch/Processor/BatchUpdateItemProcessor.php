<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\BatchUpdateItemContext;
use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultActionProcessor;
use Oro\Bundle\ApiBundle\Processor\NormalizeResultContext;

/**
 * The main processor for "batch_update_item" action.
 */
class BatchUpdateItemProcessor extends ByStepNormalizeResultActionProcessor
{
    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): BatchUpdateItemContext
    {
        return new BatchUpdateItemContext();
    }

    /**
     * {@inheritDoc}
     */
    protected function getLogContext(NormalizeResultContext $context): array
    {
        /** @var BatchUpdateItemContext $context */

        $result = parent::getLogContext($context);
        $result['class'] = $context->getClassName();
        $result['id'] = $context->getId();
        if (empty($result['id'])) {
            unset($result['id']);
        }
        $result['targetAction'] = $context->getTargetAction();

        return $result;
    }
}
