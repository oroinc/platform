<?php
namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

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
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $this->touch($filePath);

        $this->filePath = $filePath;
        $this->timestamp = filemtime($filePath);
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        if (! file_exists($this->filePath)) {
            $context->getLogger()->info(
                '[InterruptConsumptionExtension] Execution interrupted: the cache was cleared.',
                ['context' => $context]
            );

            $context->setExecutionInterrupted(true);
            $context->setInterruptedReason('The cache was cleared.');

            return;
        }

        clearstatcache(true, $this->filePath);

        if (filemtime($this->filePath) > $this->timestamp) {
            $context->getLogger()->info(
                '[InterruptConsumptionExtension] Execution interrupted: the cache was invalidated.',
                ['context' => $context]
            );

            $context->setExecutionInterrupted(true);
            $context->setInterruptedReason('The cache was cleared.');
        }
    }
}
