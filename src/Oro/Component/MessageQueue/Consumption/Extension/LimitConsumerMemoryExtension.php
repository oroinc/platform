<?php
namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class LimitConsumerMemoryExtension extends AbstractExtension
{
    /**
     * @var int
     */
    protected $memoryLimit;

    /**
     * @param int $memoryLimit Megabytes
     */
    public function __construct($memoryLimit)
    {
        if (false == is_int($memoryLimit)) {
            throw new \InvalidArgumentException(sprintf(
                'Expected memory limit is int but got: "%s"',
                is_object($memoryLimit) ? get_class($memoryLimit) : gettype($memoryLimit)
            ));
        }

        $this->memoryLimit = $memoryLimit * 1024 * 1024;
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
        $this->checkMemory($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $this->checkMemory($context);
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
        $this->checkMemory($context);
    }

    /**
     * @param Context $context
     */
    protected function checkMemory(Context $context)
    {
        $memoryUsage = memory_get_usage(true);
        if ($memoryUsage >= $this->memoryLimit) {
            $context->getLogger()->debug(sprintf(
                '[LimitConsumerMemoryExtension] Interrupt execution as memory limit reached. limit: "%s", used: "%s"',
                $this->memoryLimit,
                $memoryUsage
            ));

            $context->setExecutionInterrupted(true);
        }
    }
}
