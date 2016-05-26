<?php
namespace Oro\Component\MessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Queue;

class AmqpQueue implements Queue
{
    /**
     * @var string
     */
    private $name;

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
    private $exclusive;

    /**
     * @var boolean
     */
    private $autoDelete;

    /**
     * @var boolean
     */
    private $noWait;

    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @var boolean
     */
    private $noLocal;

    /**
     * @var boolean
     */
    private $noAck;

    /**
     * @var string[]
     */
    private $table;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->passive = false;
        $this->durable = true;
        $this->exclusive = false;
        $this->autoDelete = false;
        $this->noWait = false;

        $this->consumerTag = '';
        $this->noLocal = false;
        $this->noAck = false;
        $this->table = [];
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->name;
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
    public function isExclusive()
    {
        return $this->exclusive;
    }

    /**
     * @param boolean $exclusive
     */
    public function setExclusive($exclusive)
    {
        $this->exclusive = $exclusive;
    }

    /**
     * @return boolean
     */
    public function isAutoDelete()
    {
        return $this->autoDelete;
    }

    /**
     * @param boolean $autoDelete
     */
    public function setAutoDelete($autoDelete)
    {
        $this->autoDelete = $autoDelete;
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
    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * @param string $consumerTag
     */
    public function setConsumerTag($consumerTag)
    {
        $this->consumerTag = $consumerTag;
    }

    /**
     * @return boolean
     */
    public function isNoLocal()
    {
        return $this->noLocal;
    }

    /**
     * @param boolean $noLocal
     */
    public function setNoLocal($noLocal)
    {
        $this->noLocal = $noLocal;
    }

    /**
     * @return boolean
     */
    public function isNoAck()
    {
        return $this->noAck;
    }

    /**
     * @param boolean $noAck
     */
    public function setNoAck($noAck)
    {
        $this->noAck = $noAck;
    }

    /**
     * @return \string[]
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param \string[] $table
     */
    public function setTable(array $table)
    {
        $this->table = $table;
    }
}
