<?php
namespace Oro\Component\Messaging\ZeroConfig;

class Config
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
        $this->routerTopicName = $routerTopicName;
        $this->routerQueueName = $routerQueueName;
        $this->queueTopicName = $queueTopicName;
        $this->defaultQueueQueueName = $defaultQueueQueueName;
    }

    /**
     * @return string
     */
    public function getRouterTopicName()
    {
        return $this->formatName($this->routerTopicName);
    }

    /**
     * @return string
     */
    public function getRouterQueueName()
    {
        return $this->formatName($this->routerQueueName);
    }

    /**
     * @return string
     */
    public function getQueueTopicName()
    {
        return $this->formatName($this->queueTopicName);
    }

    /**
     * @return string
     */
    public function getDefaultQueueQueueName()
    {
        return $this->formatName($this->defaultQueueQueueName);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function formatName($name)
    {
        return strtolower(trim($this->prefix) . '.' . trim($name));
    }
}
