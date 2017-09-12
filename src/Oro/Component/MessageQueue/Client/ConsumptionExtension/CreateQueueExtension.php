<?php

namespace Oro\Component\MessageQueue\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Transport\QueueCollection;

class CreateQueueExtension extends AbstractExtension
{
    /** @var DriverInterface */
    private $driver;

    /** @var QueueCollection */
    private $createdQueues;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
        $this->createdQueues = new QueueCollection();
    }

    /**
     * @param QueueCollection $createdQueues
     * @deprecated since 2.0
     */
    public function setQueueRegistry(QueueCollection $createdQueues)
    {
        $this->createdQueues = $createdQueues;
    }

    /**
     * @param Context $context
     */
    public function onBeforeReceive(Context $context)
    {
        $queueName = $context->getQueueName();
        if ($this->createdQueues->has($queueName)) {
            return;
        }

        $this->createdQueues->set($queueName, $this->driver->createQueue($queueName));

        $context->getLogger()->debug(sprintf(
            '[CreateQueueExtension] Make sure the queue %s exists on a broker side.',
            $queueName
        ));
    }
}
