<?php
namespace Oro\Component\Messaging\Transport\Amqp;

use Oro\Component\Messaging\Transport\Exception\InvalidMessageException;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\Queue;
use Oro\Component\Messaging\Transport\MessageConsumer;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;

class AmqpMessageConsumer implements MessageConsumer
{
    /**
     * @var AmqpSession
     */
    private $session;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var bool
     */
    private $isInit;

    /**
     * @var AmqpMessage|null
     */
    private $receivedMessage;

    /**
     * @param AmqpSession $session
     * @param AmqpQueue $queue
     */
    public function __construct(AmqpSession $session, AmqpQueue $queue)
    {
        $this->isInit = false;

        $this->queue = $queue;
        $this->session = $session;
    }

    /**
     * @return Queue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpMessage|null
     */
    public function receive($timeout = 0)
    {
        $this->initialize();
        
        try {
            $this->receivedMessage = null;
            $this->session->getChannel()->wait($allowedMethods = [], $nonBlocking = false, $timeout);

            return $this->receivedMessage;
        } catch (AMQPTimeoutException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function acknowledge(Message $message)
    {
        if (false == $message instanceof AmqpMessage) {
            throw new InvalidMessageException(sprintf(
                'A message is invalid. Message must be an instance of %s but it is %s.',
                'Oro\Component\Messaging\Transport\Amqp\AmqpMessage',
                get_class($message)
            ));
        }
        $internalMessage = $message->getInternalMessage();
        if (false == $internalMessage) {
            throw new InvalidMessageException(
                'A message does not have an internal message associated. Could not be acknowledged'
            );
        }

        $this->session->getChannel()->basic_ack($internalMessage->delivery_info['delivery_tag']);
    }

    protected function initialize()
    {
        if ($this->isInit) {
            return;
        }

        $callback = function (AMQPLibMessage $internalMessage) {
            $properties = $internalMessage->has('application_headers') ?
                $internalMessage->get('application_headers')->getNativeData() :
                [];

            $message = $this->session->createMessage(
                $internalMessage->body,
                $properties,
                $internalMessage->get_properties()
            );
            $message->setInternalMessage($internalMessage);

            $this->receivedMessage = $message;
        };

        $this->session->getChannel()->basic_consume(
            $this->getQueue()->getQueueName(),
            '',
            false,
            false,
            false,
            false,
            $callback
        );

        $this->isInit = true;
    }
}
