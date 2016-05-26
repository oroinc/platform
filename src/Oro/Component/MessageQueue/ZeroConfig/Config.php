<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

class Config
{
    const PARAMETER_TOPIC_NAME = 'oro.message_queue.zero_config.topic_name';
    const PARAMETER_PROCESSOR_NAME = 'oro.message_queue.zero_config.processor_name';
    const PARAMETER_QUEUE_NAME = 'oro.message_queue.zero_config.queue_name';

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
     * @param string $prefix
     * @param string $routerMessageProcessorName
     * @param string $routerQueueName
     * @param string $defaultQueueName
     */
    public function __construct($prefix, $routerMessageProcessorName, $routerQueueName, $defaultQueueName)
    {
        $this->prefix = $prefix;
        $this->routerMessageProcessorName = $routerMessageProcessorName;
        $this->routerQueueName = $routerQueueName;
        $this->defaultQueueName = $defaultQueueName;
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
     * @param string $name
     *
     * @return string
     */
    public function formatName($name)
    {
        return trim(strtolower(trim($this->prefix) . '.' . trim($name)), '.');
    }
}
