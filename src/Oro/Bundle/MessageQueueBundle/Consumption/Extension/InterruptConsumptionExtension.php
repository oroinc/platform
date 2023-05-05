<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Checks if cache was cleared and interrupt consumer.
 */
class InterruptConsumptionExtension extends AbstractExtension
{
    public const CACHE_KEY = 'is_actual';

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

    private CacheItemPoolInterface $interruptConsumptionCache;

    public function __construct(CacheItemPoolInterface $interruptConsumptionCache, CacheState $cacheState)
    {
        $this->interruptConsumptionCache = $interruptConsumptionCache;

        $interruptConsumptionCache = $this->interruptConsumptionCache->getItem(self::CACHE_KEY);
        if (!$interruptConsumptionCache->isHit()) {
            $interruptConsumptionCache->set(true);
            $this->interruptConsumptionCache->save($interruptConsumptionCache);
        }

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
        $interruptConsumptionCache = $this->interruptConsumptionCache->getItem(self::CACHE_KEY);
        if (!$interruptConsumptionCache->isHit()) {
            $this->interruptExecution($context, 'The cache has changed.');

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
