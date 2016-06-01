<?php
namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;

class LimitConsumerMemoryExtension implements ExtensionInterface
{
    use ExtensionTrait;

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

        if ($memoryLimit <= 0) {
            throw new \LogicException(sprintf(
                'Memory limit must be more than zero but got: "%s"',
                $memoryLimit
            ));
        }

        $this->memoryLimit = $memoryLimit * 1024 * 1024;
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
        if (memory_get_usage(true) >= $this->memoryLimit) {
            $context->getLogger()->debug(
                '[LimitConsumerMemoryExtension] Interrupt execution as memory limit exceeded'
            );

            $context->setExecutionInterrupted(true);
        }
    }
}
