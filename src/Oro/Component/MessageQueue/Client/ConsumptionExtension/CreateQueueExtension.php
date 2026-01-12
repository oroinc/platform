<?php

namespace Oro\Component\MessageQueue\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\QueueCollection;

/**
 * Ensures that message queues are created on the broker before message consumption begins.
 *
 * This extension is responsible for creating queues on the message broker during the consumption
 * process. It tracks which queues have already been created to avoid redundant creation attempts,
 * and logs the queue creation process for debugging purposes.
 */
class CreateQueueExtension extends AbstractExtension
{
    /** @var DriverInterface */
    private $driver;

    /** @var QueueCollection */
    private $createdQueues;

    public function __construct(DriverInterface $driver, ?QueueCollection $createdQueues = null)
    {
        $this->driver = $driver;
        if (null === $createdQueues) {
            $createdQueues = new QueueCollection();
        }
        $this->createdQueues = $createdQueues;
    }

    #[\Override]
    public function onBeforeReceive(Context $context)
    {
        $queueName = $context->getQueueName();
        if ($this->createdQueues->has($queueName)) {
            return;
        }

        $this->createdQueues->set($queueName, $this->driver->createQueue($queueName));

        $context->getLogger()->debug(sprintf(
            'Make sure the queue "%s" exists on a broker side.',
            $queueName
        ));
    }
}
