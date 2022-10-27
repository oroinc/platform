<?php

namespace Oro\Component\MessageQueue\Client;

/**
 * Contains basic configuration data for message consuming.
 */
class Config
{
    public const PARAMETER_TOPIC_NAME = 'oro.message_queue.client.topic_name';
    public const PARAMETER_QUEUE_NAME = 'oro.message_queue.client.queue_name';
    public const DEFAULT_QUEUE_NAME = 'default';
    public const DEFAULT_TOPIC_NAME = 'default';

    private string $transportPrefix;

    private string $defaultQueueName;

    private string $defaultTopicName;

    public function __construct(
        string $transportPrefix,
        string $defaultQueueName,
        string $defaultTopicName = self::DEFAULT_TOPIC_NAME
    ) {
        $this->transportPrefix = $transportPrefix;
        $this->defaultQueueName = $defaultQueueName;
        $this->defaultTopicName = $defaultTopicName;
    }

    public function getDefaultQueueName(): string
    {
        return $this->addTransportPrefix($this->defaultQueueName);
    }

    public function getDefaultTopicName(): string
    {
        return $this->addTransportPrefix($this->defaultTopicName);
    }

    public function addTransportPrefix(string $name): string
    {
        return strtolower(trim(trim($this->transportPrefix) . '.' . trim($name), '.'));
    }

    public function removeTransportPrefix(string $name): string
    {
        return strtolower(str_ireplace(trim($this->transportPrefix) . '.', '', trim($name)));
    }
}
