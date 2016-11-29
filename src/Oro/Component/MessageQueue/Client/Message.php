<?php
namespace Oro\Component\MessageQueue\Client;

class Message
{
    /**
     * @var mixed
     */
    private $body;

    /**
     * @var string|null
     */
    private $contentType;

    /**
     * @var string
     */
    private $messageId;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var string|null
     */
    private $priority;

    /**
     * The number of seconds the message should be removed from the queue without processing
     *
     * @var int|null
     */
    private $expire;

    /**
     * The number of seconds the message should be delayed before it will be send to a queue
     *
     * @var int|null
     */
    private $delay;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @param mixed       $body     Can be null, scalar or array
     * @param string|null $priority Can be any value from {@see Oro\Component\MessageQueue\Client\MessagePriority)
     */
    public function __construct($body = null, $priority = null)
    {
        $this->body = $body;
        $this->priority = $priority;
        $this->headers = [];
        $this->properties = [];
    }

    /**
     * @return mixed Can be null, scalar or array
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body Can be null, scalar or array
     *
     * @return self
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param string|null $contentType
     *
     * @return self
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageId()
    {
        return $this->messageId;
    }

    /**
     * @param string $messageId
     *
     * @return self
     */
    public function setMessageId($messageId)
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param int $timestamp
     *
     * @return self
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * @see Oro\Component\MessageQueue\Client\MessagePriority
     *
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $priority Can be any value from {@see Oro\Component\MessageQueue\Client\MessagePriority)
     *
     * @return self
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * Gets the number of seconds the message should be removed from the queue without processing
     *
     * @return int|null
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     * @param int|null $expire
     *
     * @return self
     */
    public function setExpire($expire)
    {
        $this->expire = $expire;

        return $this;
    }

    /**
     * Gets the number of seconds the message should be delayed before it will be send to a queue
     *
     * @return int|null
     */
    public function getDelay()
    {
        return $this->delay;
    }

    /**
     * Set delay in seconds
     *
     * @param int|null $delay
     *
     * @return self
     */
    public function setDelay($delay)
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return self
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return self
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param array $properties
     *
     * @return self
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getProperty($name, $default = null)
    {
        return array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return self
     */
    public function setProperty($name, $value)
    {
        $this->properties[$name] = $value;

        return $this;
    }
}
