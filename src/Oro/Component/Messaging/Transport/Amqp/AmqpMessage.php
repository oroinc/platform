<?php
namespace Oro\Component\Messaging\Transport\Amqp;

use Oro\Component\Messaging\Transport\Message;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;

class AmqpMessage implements Message
{
    /**
     * @var string
     */
    private $body;
    
    /**
     * @var array
     */
    private $properties;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var AMQPLibMessage|null
     */
    private $internalMessage;

    public function __construct()
    {
        $this->properties = [];
        $this->headers = [];
    }
    
    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $properties
     */
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty($name, $default = null)
    {
        return array_key_exists($name, $this->properties) ?$this->properties[$name] : $default;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($name, $default = null)
    {
        return array_key_exists($name, $this->headers) ?$this->headers[$name] : $default;
    }

    /**
     * @return AMQPLibMessage|null
     */
    public function getInternalMessage()
    {
        return $this->internalMessage;
    }

    /**
     * @param AMQPLibMessage|null $internalMessage
     */
    public function setInternalMessage(AMQPLibMessage $internalMessage = null)
    {
        $this->internalMessage = $internalMessage;
    }
}
