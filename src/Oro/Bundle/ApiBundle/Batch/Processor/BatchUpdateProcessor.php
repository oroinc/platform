<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor;

use Oro\Bundle\ApiBundle\Batch\Processor\Update\BatchUpdateContext;
use Oro\Bundle\ApiBundle\Processor\ByStepNormalizeResultActionProcessor;

/**
 * The main processor for "batch_update" action.
 */
class BatchUpdateProcessor extends ByStepNormalizeResultActionProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new BatchUpdateContext();
    }
}
