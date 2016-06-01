<?php
namespace Oro\Component\MessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;
use PhpAmqpLib\Wire\AMQPAbstractCollection;
use PhpAmqpLib\Wire\AMQPArray;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpMessageConsumer implements MessageConsumerInterface
{
    /**
     * @var AmqpSession
     */
    private $session;

    /**
     * @var QueueInterface
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
     * {@inheritdoc}
     *
     * @return AmqpQueue
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
     * @return AmqpMessage|null
     */
    public function receiveNoWait()
    {
        if ($internalMessage = $this->session->getChannel()->basic_get($this->queue->getQueueName(), $noAck = false)) {
            return $this->convertMessage($internalMessage);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function acknowledge(MessageInterface $message)
    {
        if (false == $message instanceof AmqpMessage) {
            throw new InvalidMessageException(sprintf(
                'A message is invalid. Message must be an instance of %s but it is %s.',
                AmqpMessage::class,
                get_class($message)
            ));
        }

        $this->session->getChannel()->basic_ack($message->getDeliveryTag());
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpMessage $message
     */
    public function reject(MessageInterface $message, $requeue = false)
    {
        if (false == $message instanceof AmqpMessage) {
            throw new InvalidMessageException(sprintf(
                'A message is invalid. Message must be an instance of %s but it is %s.',
                AmqpMessage::class,
                get_class($message)
            ));
        }

        $this->session->getChannel()->basic_reject($message->getDeliveryTag(), $requeue);
    }

    protected function initialize()
    {
        if ($this->isInit) {
            return;
        }

        $callback = function (AMQPLibMessage $internalMessage) {
            $this->receivedMessage = $this->convertMessage($internalMessage);
        };

        $this->session->getChannel()->basic_consume(
            $this->queue->getQueueName(),
            $this->queue->getConsumerTag(),
            $this->queue->isNoLocal(),
            $this->queue->isNoAck(),
            $this->queue->isExclusive(),
            $this->queue->isNoWait(),
            $callback
        );

        $this->isInit = true;
    }

    /**
     * @param AMQPLibMessage $internalMessage
     *
     * @return AmqpMessage
     */
    protected function convertMessage(AMQPLibMessage $internalMessage)
    {
        $properties = $internalMessage->has('application_headers') ?
            $internalMessage->get('application_headers')->getNativeData() :
            [];

        $headers = (new AMQPTable($internalMessage->get_properties()))->getNativeData();
        unset($headers['application_headers']);

        $message = $this->session->createMessage($internalMessage->body, $properties, $headers);
        $message->setDeliveryTag($internalMessage->delivery_info['delivery_tag']);
        $message->setRedelivered($internalMessage->delivery_info['redelivered']);
        $message->setExchange($internalMessage->delivery_info['exchange']);

        return $message;
    }
}
