<?php
namespace Oro\Bundle\MessageQueueBundle\EventListener;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\InterruptConsumptionExtension;
use Oro\Bundle\MessageQueueBundle\Consumption\InterruptConsumptionExtensionTrait;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Interrupts consumption on schema update event
 */
class UpdateSchemaListener
{
    use InterruptConsumptionExtensionTrait;

    private ?CacheItemPoolInterface $interruptConsumptionCache = null;

    /**
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function setInterruptConsumptionCache(?CacheItemPoolInterface $interruptConsumptionCache = null): void
    {
        $this->interruptConsumptionCache = $interruptConsumptionCache;
    }

    /**
     * Clears "Interrupt Consumption" cache
     */
    public function interruptConsumption()
    {
        if ($this->interruptConsumptionCache instanceof CacheItemPoolInterface) {
            $this->interruptConsumptionCache->deleteItem(InterruptConsumptionExtension::CACHE_KEY);
        } else {
            $this->touch($this->filePath);

            touch($this->filePath); // update file metadata
        }
    }
}
