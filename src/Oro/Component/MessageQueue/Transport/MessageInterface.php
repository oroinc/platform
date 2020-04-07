<?php

namespace Oro\Component\MessageQueue\Transport;

/**
 * The Message interface is the root interface of all transport messages.
 * Messages consist of a header and a payload.
 * The header contains fields used for message routing and identification,
 * the payload contains the application data being sent.
 */
interface MessageInterface
{
    /**
     * @return string
     */
    public function getBody(): string;

    /**
     * @param string $body
     *
     * @return void
     */
    public function setBody(string $body): void;

    /**
     * @param array $properties
     *
     * @return void
     */
    public function setProperties(array $properties): void;

    /**
     * @return array [name => value, ...]
     */
    public function getProperties(): array;

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function getProperty(string $name, string $default = ''): string;

    /**
     * @param array $headers
     *
     * @return void
     */
    public function setHeaders(array $headers): void;

    /**
     * @return array [name => value, ...]
     */
    public function getHeaders(): array;

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function getHeader(string $name, string $default = ''): string;

    /**
     * @param bool $redelivered
     *
     * @return void
     */
    public function setRedelivered(bool $redelivered): void;

    /**
     * Gets an indication of whether this message is being redelivered.
     * The message is considered as redelivered,
     * when it was sent by a broker to consumer but consumer does not ACK or REJECT it.
     * The broker brings the message back to the queue and mark it as redelivered.
     *
     * @return bool
     */
    public function isRedelivered(): bool;

    /**
     * Sets the correlation ID for the message.
     * A client can use the correlation header field to link one message with another.
     * A typical use is to link a response message with its request message.
     *
     * @param string $correlationId the message ID of a message being referred to
     *
     * @return void
     */
    public function setCorrelationId(string $correlationId): void;

    /**
     * Gets the correlation ID for the message.
     * This method is used to return correlation ID values that are either provider-specific message IDs
     * or application-specific String values.
     *
     * @return string
     */
    public function getCorrelationId(): string;

    /**
     * Sets the message ID.
     * Providers set this field when a message is sent.
     * This method can be used to change the value for a message that has been received.
     *
     * @param string $messageId the ID of the message
     *
     * @return void
     */
    public function setMessageId(string $messageId): void;

    /**
     * Gets the message Id.
     * The MessageId header field contains a value that uniquely identifies each message sent by a provider.
     *
     * When a message is sent, MessageId can be ignored.
     *
     * @return string
     */
    public function getMessageId(): string;

    /**
     * Gets the message timestamp.
     * The Timestamp header field contains the time a message was handed off to a provider to be sent.
     * It is not the time the message was actually transmitted,
     * because the actual send may occur later due to transactions or other client-side queueing of messages.
     *
     * @return int
     */
    public function getTimestamp(): int;

    /**
     * Sets the message timestamp.
     * Providers set this field when a message is sent.
     * This method can be used to change the value for a message that has been received.
     *
     * @param int $timestamp
     *
     * @return void
     */
    public function setTimestamp(int $timestamp): void;

    /**
     * Gets the message priority.
     * The Priority header field tells how the message should be prioritised. Larger numbers indicate higher priority.
     *
     * @return int
     */
    public function getPriority(): int;

    /**
     * Sets the message priority.
     * Providers set this field when a message is sent.
     * This method can be used to change the value for a message that has been received.
     *
     * @param int $priority
     *
     * @return void
     */
    public function setPriority(int $priority): void;

    /**
     * Gets the message delay.
     * The Delay property field contains the time that postpones message processing. In seconds.
     *
     * @return int
     */
    public function getDelay(): int;

    /**
     * Sets the message delay.
     * Providers set this field when a message is sent.
     * This method can be used to change the value for a message that has been received.
     *
     * @param int $delay
     *
     * @return void
     */
    public function setDelay(int $delay): void;
}
