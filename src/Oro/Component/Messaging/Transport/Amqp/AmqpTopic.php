<?php
namespace Oro\Component\Messaging\Transport\Amqp;

use Oro\Component\Messaging\Transport\Topic;

class AmqpTopic implements Topic
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var boolean
     */
    private $passive;

    /**
     * @var boolean
     */
    private $durable;

    /**
     * @var boolean
     */
    private $noWait;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var boolean
     */
    private $mandatory;

    /**
     * @var boolean
     */
    private $immediate;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->type = 'fanout';
        $this->passive = false;
        $this->durable = false;
        $this->noWait = false;
        
        $this->routingKey = '';
        $this->mandatory = false;
        $this->immediate = false;
    }

    /**
     * @return string
     */
    public function getTopicName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return boolean
     */
    public function isPassive()
    {
        return $this->passive;
    }

    /**
     * @param boolean $passive
     */
    public function setPassive($passive)
    {
        $this->passive = $passive;
    }

    /**
     * @return boolean
     */
    public function isDurable()
    {
        return $this->durable;
    }

    /**
     * @param boolean $durable
     */
    public function setDurable($durable)
    {
        $this->durable = $durable;
    }

    /**
     * @return boolean
     */
    public function isNoWait()
    {
        return $this->noWait;
    }

    /**
     * @param boolean $noWait
     */
    public function setNoWait($noWait)
    {
        $this->noWait = $noWait;
    }

    /**
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * @param string $routingKey
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
    }

    /**
     * @return boolean
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @param boolean $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    /**
     * @return boolean
     */
    public function isImmediate()
    {
        return $this->immediate;
    }

    /**
     * @param boolean $immediate
     */
    public function setImmediate($immediate)
    {
        $this->immediate = $immediate;
    }
}