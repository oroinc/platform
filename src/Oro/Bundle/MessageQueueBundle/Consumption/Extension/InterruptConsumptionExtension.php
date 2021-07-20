<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use Oro\Bundle\MessageQueueBundle\Consumption\InterruptConsumptionExtensionTrait;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Checks if cache was cleared and interrupt consumer.
 */
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
    protected static $startTime;

    /**
     * @var CacheState
     */
    protected $cacheState;

    /**
     * @param string $filePath
     * @param CacheState $cacheState
     */
    public function __construct($filePath, CacheState $cacheState)
    {
        $this->touch($filePath);

        $this->filePath = $filePath;
        $this->timestamp = filemtime($filePath);
        $this->cacheState = $cacheState;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context): void
    {
        self::$startTime = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context): void
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
        if ($cacheChangeDate && $cacheChangeDate > self::$startTime) {
            $this->interruptExecution($context, 'The cache has changed.');
        }
    }

    private function interruptExecution(Context $context, string $reason): void
    {
        $context->getLogger()->info(
            'Execution interrupted: ' . $reason,
            ['context' => $context]
        );

        $context->setExecutionInterrupted(true);
        $context->setInterruptedReason($reason);
    }
}
