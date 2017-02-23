<?php
namespace Oro\Bundle\MessageQueueBundle\EventListener;

use Oro\Bundle\MessageQueueBundle\Consumption\InterruptConsumptionExtensionTrait;

class UpdateSchemaListener
{
    use InterruptConsumptionExtensionTrait;

    /**
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Interrupt consumption on schema update event
     */
    public function interruptConsumption()
    {
        $this->touch($this->filePath);

        touch($this->filePath); // update file metadata
    }
}
