<?php
namespace Oro\Bundle\MessageQueueBundle\EventListener;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\InterruptConsumptionExtension;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Interrupts consumption on schema update event
 */
class UpdateSchemaListener
{
    private CacheItemPoolInterface $interruptConsumptionCache;

    public function __construct(CacheItemPoolInterface $interruptConsumptionCache)
    {
        $this->interruptConsumptionCache = $interruptConsumptionCache;
    }

    /**
     * Clears "Interrupt Consumption" cache
     */
    public function onSchemaUpdate(): void
    {
        $this->interruptConsumptionCache->deleteItem(InterruptConsumptionExtension::CACHE_KEY);
    }
}
