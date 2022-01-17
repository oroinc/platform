<?php

namespace Oro\Component\MessageQueue\Transport;

/**
 * Base transport message that implements Message interface.
 * @see \Oro\Component\MessageQueue\Transport\MessageInterface
 */
class Message implements MessageInterface
{
    private mixed $body = '';

    /** @var array */
    private $properties = [];

    /** @var array */
    private $headers = [];

    /** @var bool */
    private $redelivered = false;

    public function setBody(mixed $body): void
    {
        $this->body = $body;
    }

    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty(string $name, string $default = ''): string
    {
        return array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader(string $name, string $default = ''): string
    {
        return array_key_exists($name, $this->headers) ?$this->headers[$name] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function isRedelivered(): bool
    {
        return $this->redelivered;
    }

    /**
     * {@inheritdoc}
     */
    public function setRedelivered(bool $redelivered): void
    {
        $this->redelivered = $redelivered;
    }

    /**
     * {@inheritdoc}
     */
    public function setCorrelationId(string $correlationId): void
    {
        $headers = $this->getHeaders();
        $headers['correlation_id'] = $correlationId;

        $this->setHeaders($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function getCorrelationId(): string
    {
        return $this->getHeader('correlation_id');
    }

    /**
     * {@inheritdoc}
     */
    public function setMessageId(string $messageId): void
    {
        $headers = $this->getHeaders();
        $headers['message_id'] = $messageId;

        $this->setHeaders($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function getMessageId(): string
    {
        return $this->getHeader('message_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp(): int
    {
        return (int)$this->getHeader('timestamp');
    }

    /**
     * {@inheritdoc}
     */
    public function setTimestamp(int $timestamp): void
    {
        $headers = $this->getHeaders();
        $headers['timestamp'] = (string)$timestamp;

        $this->setHeaders($headers);
    }

    /**
     * @inheritdoc
     */
    public function getPriority(): int
    {
        return (int)$this->getHeader('priority');
    }

    /**
     * @inheritdoc
     */
    public function setPriority(int $priority): void
    {
        $headers = $this->getHeaders();
        $headers['priority'] = (string)$priority;

        $this->setHeaders($headers);
    }

    /**
     * @inheritdoc
     */
    public function getDelay(): int
    {
        return (int)$this->getProperty('delay');
    }

    /**
     * @inheritdoc
     */
    public function setDelay(int $delay): void
    {
        $properties = $this->getProperties();
        $properties['delay'] = (string)$delay;

        $this->setProperties($properties);
    }
}
