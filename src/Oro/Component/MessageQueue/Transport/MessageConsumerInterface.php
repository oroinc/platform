<?php
namespace Oro\Component\MessageQueue\Transport;

/**
 * A client uses a MessageConsumer object to receive messages from a destination.
 * A MessageConsumer object is created by passing a Destination object
 * to a message-consumer creation method supplied by a session.
 *
 * @link https://docs.oracle.com/javaee/1.4/api/javax/jms/MessageConsumer.html
 */
interface MessageConsumerInterface
{
    /**
     * Gets the Queue associated with this queue receiver.
     *
     * @return QueueInterface
     */
    public function getQueue();

    /**
     * Receives the next message that arrives within the specified timeout interval.
     * This call blocks until a message arrives, the timeout expires, or this message consumer is closed.
     * A timeout of zero never expires, and the call blocks indefinitely.
     *
     * @param int $timeout the timeout value (in milliseconds)
     *
     * @return MessageInterface|null
     */
    public function receive($timeout = 0);

    /**
     * Receives the next message if one is immediately available.
     *
     * @return MessageInterface|null
     */
    public function receiveNoWait();
    
    /**
     * Tell the MQ broker that the message was processed successfully
     *
     * @param MessageInterface $message
     *
     * @return void
     */
    public function acknowledge(MessageInterface $message);

    /**
     * Tell the MQ broker that the message was rejected
     *
     * @param MessageInterface $message
     * @param bool $requeue
     *
     * @return void
     */
    public function reject(MessageInterface $message, $requeue = false);
}
