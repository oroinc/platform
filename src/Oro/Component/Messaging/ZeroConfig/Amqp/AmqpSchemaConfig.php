<?php
namespace Oro\Component\Messaging\ZeroConfig\Amqp;

use Oro\Component\Messaging\ZeroConfig\SchemaConfigInterface;

class AmqpSchemaConfig implements SchemaConfigInterface
{
    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var string
     */
    protected $routerTopicName;

    /**
     * @var string
     */
    protected $routerQueueName;

    /**
     * @var string
     */
    protected $queueTopicName;

    /**
     * @var string
     */
    protected $defaultQueueQueueName;

    /**
     * @param $prefix
     * @param $routerTopicName
     * @param $routerQueueName
     * @param $queueTopicName
     * @param $defaultQueueQueueName
     */
    public function __construct($prefix, $routerTopicName, $routerQueueName, $queueTopicName, $defaultQueueQueueName)
    {
        $this->prefix = $prefix;

        $this->routerTopicName = $this->formatName($routerTopicName);
        $this->routerQueueName = $this->formatName($routerQueueName);
        $this->queueTopicName = $this->formatName($queueTopicName);
        $this->defaultQueueQueueName = $this->formatName($defaultQueueQueueName);
    }

    /**
     * {@inheritdoc}
     */
    public function getRouterTopicName()
    {
        return $this->routerTopicName;
    }

    /**
     * {@inheritdoc}
     */
    public function getRouterQueueName()
    {
        return $this->routerQueueName;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueTopicName()
    {
        return $this->queueTopicName;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueQueueName($queueName = null)
    {
        return $queueName ? $this->formatName($queueName) : $this->defaultQueueQueueName;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function formatName($name)
    {
        return strtolower(trim($this->prefix) . '.' . trim($name));
    }
}
