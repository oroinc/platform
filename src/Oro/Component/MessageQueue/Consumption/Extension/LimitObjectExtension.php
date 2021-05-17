<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Stop consumption when amount of objects in runtime reached
 */
class LimitObjectExtension extends AbstractExtension
{
    /** @var int */
    protected $objectLimit;

    public function __construct(int $objectLimit)
    {
        $this->objectLimit = $objectLimit;
    }

    public function onStart(Context $context)
    {
        $this->checkObjectLimit($context);
    }

    public function onIdle(Context $context)
    {
        $this->checkObjectLimit($context);
    }

    public function onPreReceived(Context $context)
    {
        $this->checkObjectLimit($context);
    }

    public function onBeforeReceive(Context $context)
    {
        $this->checkObjectLimit($context);
    }

    public function onPostReceived(Context $context)
    {
        $this->checkObjectLimit($context);
    }

    protected function checkObjectLimit(Context $context): void
    {
        $objectAmount = \spl_object_id(new \stdClass());

        if ($objectAmount >= $this->objectLimit) {
            $context->getLogger()->debug(
                sprintf(
                    'Message consumption is interrupted since the object limit reached. limit: "%s"',
                    $this->objectLimit
                )
            );

            $context->setExecutionInterrupted(true);
            $context->setInterruptedReason('The object limit reached.');
        }
    }
}
