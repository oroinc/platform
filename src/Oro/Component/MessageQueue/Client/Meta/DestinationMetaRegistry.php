<?php

namespace Oro\Component\MessageQueue\Client\Meta;

use Oro\Component\MessageQueue\Client\Config;

/**
 * Registry of queues-subscribers relations.
 */
class DestinationMetaRegistry
{
    private Config $config;

    /**
     * @var array
     *  [
     *      'queue_name' => [
     *          'message_processor1',
     *          'message_processor2',
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    private array $messageProcessorsByQueue;

    /**
     * @param Config $config
     * @param array $messageProcessorsByQueue
     *  [
     *      'queue_name' => [
     *          'message_processor1',
     *          'message_processor2',
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    public function __construct(Config $config, array $messageProcessorsByQueue)
    {
        $this->config = $config;
        $this->messageProcessorsByQueue = $messageProcessorsByQueue;
    }

    public function getDestinationMeta(string $queueName): DestinationMeta
    {
        $queueName = strtolower($queueName);

        return new DestinationMeta(
            $queueName,
            $this->config->addTransportPrefix($queueName),
            $this->messageProcessorsByQueue[$queueName] ?? []
        );
    }

    public function getDestinationMetaByTransportQueueName(string $transportQueueName): DestinationMeta
    {
        return $this->getDestinationMeta($this->config->removeTransportPrefix($transportQueueName));
    }

    /**
     * @return iterable<DestinationMeta>
     */
    public function getDestinationsMeta(): iterable
    {
        foreach (array_keys($this->messageProcessorsByQueue) as $queueName) {
            yield $this->getDestinationMeta($queueName);
        }
    }
}
