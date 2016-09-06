<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class LimitConsumptionEmptyQueueExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        $context->getLogger()->debug('[LimitConsumptionEmptyExtension] Execution interrupted as queue is empty.');
        $context->setExecutionInterrupted(true);
    }
}
