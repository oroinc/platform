<?php
namespace Oro\Component\MessageQueue\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

class DelayRedeliveredMessageExtension extends AbstractExtension
{
    const PROPERTY_REDELIVER_COUNT = 'oro-redeliver-count';

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var int
     */
    private $delaySec;

    /**
     * @param DriverInterface $driver
     * @param int             $delaySec
     */
    public function __construct(DriverInterface $driver, $delaySec)
    {
        $this->driver = $driver;
        $this->delaySec = $delaySec;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        $message = $context->getMessage();
        if (false == $message->isRedelivered()) {
            return;
        }

        $queue = $context->getSession()->createQueue($context->getQueueName());

        $prevProperties = $message->getProperties();
        $newProperties = $message->getProperties();

        if (! isset($newProperties[self::PROPERTY_REDELIVER_COUNT])) {
            $newProperties[self::PROPERTY_REDELIVER_COUNT] = 1;
        } else {
            $newProperties[self::PROPERTY_REDELIVER_COUNT]++;
        }

        $message->setProperties($newProperties);

        $this->driver->delayMessage($queue, $message, $this->delaySec);
        $context->getLogger()->debug('[DelayRedeliveredMessageExtension] Send delayed message');

        $message->setProperties($prevProperties);

        $context->setStatus(MessageProcessorInterface::REJECT);
        $context->getLogger()->debug('[DelayRedeliveredMessageExtension] Set reject message status to context');
    }
}
