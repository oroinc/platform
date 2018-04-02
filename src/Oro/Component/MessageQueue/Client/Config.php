<?php

namespace Oro\Component\MessageQueue\Client;

class Config
{
    const PARAMETER_TOPIC_NAME = 'oro.message_queue.client.topic_name';
    const PARAMETER_PROCESSOR_NAME = 'oro.message_queue.client.processor_name';
    const PARAMETER_QUEUE_NAME = 'oro.message_queue.client.queue_name';
    const DEFAULT_QUEUE_NAME = 'default';
    const DEFAULT_TOPIC_NAME = 'default';

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $defaultQueueName;

    /**
     * @var string
     */
    private $routerMessageProcessorName;

    /**
     * @var string
     */
    private $routerQueueName;

    /**
     * @var string
     */
    private $defaultTopicName;

    /**
     * @param string $prefix
     * @param string $routerMessageProcessorName
     * @param string $routerQueueName
     * @param string $defaultQueueName
     * @param string $defaultTopicName
     */
    public function __construct(
        $prefix,
        $routerMessageProcessorName,
        $routerQueueName,
        $defaultQueueName,
        $defaultTopicName = self::DEFAULT_TOPIC_NAME
    ) {
        $this->prefix = $prefix;
        $this->routerMessageProcessorName = $routerMessageProcessorName;
        $this->routerQueueName = $routerQueueName;
        $this->defaultQueueName = $defaultQueueName;
        $this->defaultTopicName = $defaultTopicName;
    }

    /**
     * @return string
     */
    public function getRouterMessageProcessorName()
    {
        return $this->routerMessageProcessorName;
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
    public function getDefaultQueueName()
    {
        return $this->formatName($this->defaultQueueName);
    }

    /**
     * @return string
     */
    public function getDefaultTopicName()
    {
        return $this->formatName($this->defaultTopicName);
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public function formatName($name)
    {
        return trim(strtolower(trim($this->prefix) . '.' . trim($name)), '.');
    }
}
