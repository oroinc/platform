<?php
namespace Oro\Component\MessageQueue\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
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
     * The number of seconds the message should be delayed
     *
     * @var int
     */
    private $delay;

    /**
     * @param DriverInterface $driver
     * @param int             $delay The number of seconds the message should be delayed
     */
    public function __construct(DriverInterface $driver, $delay)
    {
        $this->driver = $driver;
        $this->delay = $delay;
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

        $properties = $message->getProperties();
        if (! isset($properties[self::PROPERTY_REDELIVER_COUNT])) {
            $properties[self::PROPERTY_REDELIVER_COUNT] = 1;
        } else {
            $properties[self::PROPERTY_REDELIVER_COUNT]++;
        }

        $delayedMessage = new Message();
        $delayedMessage->setBody($message->getBody());
        $delayedMessage->setHeaders($message->getHeaders());
        $delayedMessage->setProperties($properties);
        $delayedMessage->setDelay($this->delay);
        $delayedMessage->setMessageId($message->getMessageId());

        $queue = $context->getSession()->createQueue($context->getQueueName());

        $this->driver->send($queue, $delayedMessage);
        $context->getLogger()->debug('[DelayRedeliveredMessageExtension] Send delayed message');

        $context->setStatus(MessageProcessorInterface::REJECT);
        $context->getLogger()->debug(
            '[DelayRedeliveredMessageExtension] '.
            'Reject redelivered original message by setting reject status to context.'
        );
    }
}
