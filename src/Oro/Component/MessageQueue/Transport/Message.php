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

    #[\Override]
    public function setBody(mixed $body): void
    {
        $this->body = $body;
    }

    #[\Override]
    public function getBody(): mixed
    {
        return $this->body;
    }

    #[\Override]
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    #[\Override]
    public function getProperties(): array
    {
        return $this->properties;
    }

    #[\Override]
    public function getProperty(string $name, string $default = ''): string
    {
        return array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    #[\Override]
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    #[\Override]
    public function getHeaders(): array
    {
        return $this->headers;
    }

    #[\Override]
    public function getHeader(string $name, string $default = ''): string
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
    }

    #[\Override]
    public function isRedelivered(): bool
    {
        return $this->redelivered;
    }

    #[\Override]
    public function setRedelivered(bool $redelivered): void
    {
        $this->redelivered = $redelivered;
    }

    #[\Override]
    public function setCorrelationId(string $correlationId): void
    {
        $headers = $this->getHeaders();
        $headers['correlation_id'] = $correlationId;

        $this->setHeaders($headers);
    }

    #[\Override]
    public function getCorrelationId(): string
    {
        return $this->getHeader('correlation_id');
    }

    #[\Override]
    public function setMessageId(string $messageId): void
    {
        $headers = $this->getHeaders();
        $headers['message_id'] = $messageId;

        $this->setHeaders($headers);
    }

    #[\Override]
    public function getMessageId(): string
    {
        return $this->getHeader('message_id');
    }

    #[\Override]
    public function getTimestamp(): int
    {
        return (int)$this->getHeader('timestamp');
    }

    #[\Override]
    public function setTimestamp(int $timestamp): void
    {
        $headers = $this->getHeaders();
        $headers['timestamp'] = (string)$timestamp;

        $this->setHeaders($headers);
    }

    #[\Override]
    public function getPriority(): int
    {
        return (int)$this->getHeader('priority');
    }

    #[\Override]
    public function setPriority(int $priority): void
    {
        $headers = $this->getHeaders();
        $headers['priority'] = (string)$priority;

        $this->setHeaders($headers);
    }

    #[\Override]
    public function getDelay(): int
    {
        return (int)$this->getProperty('delay');
    }

    #[\Override]
    public function setDelay(int $delay): void
    {
        $properties = $this->getProperties();
        $properties['delay'] = (string)$delay;

        $this->setProperties($properties);
    }
}
