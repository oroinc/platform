<?php
namespace Oro\Component\MessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Message;

class AmqpMessage implements Message
{
    const DELIVERY_MODE_NON_PERSISTENT = 1;
    const DELIVERY_MODE_PERSISTENT = 2;

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
    private $localProperties;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var boolean
     */
    private $redelivered;

    /**
     * @var string
     */
    private $deliveryTag;

    /**
     * @var string
     */
    private $exchange;

    public function __construct()
    {
        $this->properties = [];
        $this->headers = [];
        $this->localProperties = [];

        $this->redelivered = false;
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
        return array_key_exists($name, $this->properties) ? $this->properties[$name] : $default;
    }

    /**
     * @return array
     */
    public function getLocalProperties()
    {
        return $this->localProperties;
    }

    /**
     * @param array $localProperties
     */
    public function setLocalProperties(array $localProperties)
    {
        $this->localProperties = $localProperties;
    }

    /**
     * {@inheritdoc}
     */
    public function getLocalProperty($name, $default = null)
    {
        return array_key_exists($name, $this->localProperties) ? $this->localProperties[$name] : $default;
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
     * @return boolean
     */
    public function isRedelivered()
    {
        return $this->redelivered;
    }

    /**
     * @param boolean $redelivered
     */
    public function setRedelivered($redelivered)
    {
        $this->redelivered = $redelivered;
    }

    /**
     * @return string
     */
    public function getDeliveryTag()
    {
        return $this->deliveryTag;
    }

    /**
     * @param string $deliveryTag
     */
    public function setDeliveryTag($deliveryTag)
    {
        $this->deliveryTag = $deliveryTag;
    }

    /**
     * @return string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * @param string $exchange
     */
    public function setExchange($exchange)
    {
        $this->exchange = $exchange;
    }
}
