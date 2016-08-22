<?php
namespace Oro\Component\MessageQueue\Transport;

use Oro\Component\MessageQueue\Transport\Exception\Exception;

/**
 * The Message interface is the root interface of all transport messages.
 * Most message-oriented middleware (MOM) products
 * treat messages as lightweight entities that consist of a header and a payload.
 * The header contains fields used for message routing and identification;
 * the payload contains the application data being sent.
 *
 * Within this general form, the definition of a message varies significantly across products.
 *
 * @link https://docs.oracle.com/javaee/1.4/api/javax/jms/Message.html
 */
interface MessageInterface
{
    /**
     * @return string
     */
    public function getBody();

    /**
     * @param string $body
     *
     * @return void
     */
    public function setBody($body);

    /**
     * @param array $properties
     *
     * @return void
     */
    public function setProperties(array $properties);

    /**
     * @return array [name => value, ...]
     */
    public function getProperties();

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getProperty($name, $default = null);

    /**
     * @param array $headers
     *
     * @return void
     */
    public function setHeaders(array $headers);

    /**
     * @return array [name => value, ...]
     */
    public function getHeaders();

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getHeader($name, $default = null);

    /**
     * @param boolean $redelivered
     */
    public function setRedelivered($redelivered);

    /**
     * Gets an indication of whether this message is being redelivered.
     * The message is considered as redelivered,
     * when it was sent by a broker to consumer but consumer does not ACK or REJECT it.
     * The broker brings the message back to the queue and mark it as redelivered.
     *
     * @return boolean
     */
    public function isRedelivered();

    /**
     * Sets the correlation ID for the message.
     * A client can use the correlation header field to link one message with another.
     * A typical use is to link a response message with its request message.
     *
     * @param string $correlationId the message ID of a message being referred to
     *
     * @throws Exception if the provider fails to set the correlation ID due to some internal error.
     *
     * @return void
     */
    public function setCorrelationId($correlationId);

    /**
     * Gets the correlation ID for the message.
     * This method is used to return correlation ID values that are either provider-specific message IDs
     * or application-specific String values.
     *
     * @throws Exception if the provider fails to get the correlation ID due to some internal error.
     * @return string
     */
    public function getCorrelationId();

    /**
     * Sets the message ID.
     * Providers set this field when a message is sent.
     * This method can be used to change the value for a message that has been received.
     *
     * @param string $messageId the ID of the message
     *
     * @throws Exception if the provider fails to set the message ID due to some internal error.
     *
     * @return void
     */
    public function setMessageId($messageId);

    /**
     * Gets the message Id.
     * The MessageId header field contains a value that uniquely identifies each message sent by a provider.
     *
     * When a message is sent, MessageId can be ignored.
     *
     * @throws Exception if the provider fails to get the message ID due to some internal error.
     *
     * @return string
     */
    public function getMessageId();

    /**
     * Gets the message timestamp.
     * The JMSTimestamp header field contains the time a message was handed off to a provider to be sent.
     * It is not the time the message was actually transmitted,
     * because the actual send may occur later due to transactions or other client-side queueing of messages.
     *
     * @return int
     */
    public function getTimestamp();

    /**
     * Sets the message timestamp.
     * Providers set this field when a message is sent.
     * This method can be used to change the value for a message that has been received.
     *
     * @param int $timestamp
     *
     * @throws Exception if the provider fails to set the timestamp due to some internal error.
     *
     * @return void
     */
    public function setTimestamp($timestamp);

    /**
     * @param string|null $replyTo
     */
    public function setReplyTo($replyTo);

    /**
     * @return string|null
     */
    public function getReplyTo();
}
