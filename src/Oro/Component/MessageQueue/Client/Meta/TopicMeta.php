<?php

namespace Oro\Component\MessageQueue\Client\Meta;

use Oro\Component\MessageQueue\Client\Config;

/**
 * Holds meta information about topic: bound queues and message processors.
 */
class TopicMeta
{
    private string $name;

    /**
     * @var string[]
     */
    private array $queueNames;

    /**
     * @var array
     *  [
     *      'queue_name1' => [
     *          'message_processor1',
     *          'message_processor2',
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    private array $messageProcessorByQueueName;

    /**
     * @param string $name
     * @param string[] $queueNames
     * @param array $messageProcessorByQueueName
     *  [
     *      'queue_name1' => [
     *          'message_processor1',
     *          'message_processor2',
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    public function __construct(string $name, array $queueNames = [], array $messageProcessorByQueueName = [])
    {
        $this->name = $name;
        $this->queueNames = $queueNames ?: [Config::DEFAULT_QUEUE_NAME];
        $this->messageProcessorByQueueName = $messageProcessorByQueueName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getQueueNames(): array
    {
        return $this->queueNames;
    }

    /**
     * @param string $queueName
     * @return string
     */
    public function getMessageProcessorName(string $queueName = ''): string
    {
        return (string)($this->messageProcessorByQueueName[$queueName ?: Config::DEFAULT_QUEUE_NAME] ?? '');
    }

    public function getAllMessageProcessors(): array
    {
        return array_unique(array_values($this->messageProcessorByQueueName));
    }
}
