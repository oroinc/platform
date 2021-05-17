<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Stop consumption when GC limit reached
 */
class LimitGarbageCollectionExtension extends AbstractExtension
{
    /** @var int */
    protected $garbageCollectionLimit;

    public function __construct(int $garbageCollectionLimit)
    {
        $this->garbageCollectionLimit = $garbageCollectionLimit;
    }

    public function onBeforeReceive(Context $context)
    {
        $this->checkGarbageCollectionLimit($context);
    }

    public function onPostReceived(Context $context)
    {
        $this->checkGarbageCollectionLimit($context);
    }

    protected function checkGarbageCollectionLimit(Context $context): void
    {
        if (!gc_enabled()) {
            return;
        }

        if (!function_exists('gc_status')) {
            return;
        }

        $garbageCollectionRuns = (int)gc_status()['runs'];

        if ($garbageCollectionRuns >= $this->garbageCollectionLimit) {
            $context->getLogger()->debug(
                sprintf(
                    'Message consumption is interrupted since the GC runs limit reached. limit: "%s"',
                    $this->garbageCollectionLimit
                )
            );

            $context->setExecutionInterrupted(true);
            $context->setInterruptedReason('The GC runs limit reached.');
        }
    }
}
