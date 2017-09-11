<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use Oro\Bundle\MessageQueueBundle\Consumption\InterruptConsumptionExtensionTrait;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class InterruptConsumptionExtension extends AbstractExtension
{
    use InterruptConsumptionExtensionTrait;

    /**
     * @var int
     */
    protected $timestamp;

    /**
     * Date time when the consumer was started.
     *
     * @var \DateTime
     */
    protected $startTime;

    /**
     * @var CacheState
     */
    protected $cacheState;

    /**
     * @param string $filePath
     */
    public function __construct($filePath, CacheState $cacheState)
    {
        $this->touch($filePath);

        $this->filePath = $filePath;
        $this->timestamp = filemtime($filePath);
        $this->startTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->cacheState = $cacheState;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        if (!file_exists($this->filePath)) {
            $this->interruptExecution($context, 'The cache was cleared.');

            return;
        }

        clearstatcache(true, $this->filePath);
        if (filemtime($this->filePath) > $this->timestamp) {
            $this->interruptExecution($context, 'The cache was invalidated.');

            return;
        }

        $cacheChangeDate = $this->cacheState->getChangeDate();
        if ($cacheChangeDate && $cacheChangeDate > $this->startTime) {
            $this->interruptExecution($context, 'The cache has changed.');
        }
    }

    /**
     * @param Context $context
     * @param string  $reason
     */
    private function interruptExecution(Context $context, $reason)
    {
        $context->getLogger()->info(
            'Execution interrupted: ' . $reason,
            ['context' => $context]
        );

        $context->setExecutionInterrupted(true);
        $context->setInterruptedReason($reason);
    }
}
