<?php

namespace Oro\Component\MessageQueue\Client\Meta;

use Oro\Component\MessageQueue\Client\Config;

/**
 * Registry of topics-queues-subscribers relations.
 */
class TopicMetaRegistry
{
    /**
     * @var array
     *  [
     *      'topic_name1' => [
     *          'queue_name1' => 'message_processor1',
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    private array $messageProcessorsByTopicAndQueue;

    /**
     * @var array
     *  [
     *      'topic_name1' => [
     *          'queue_name1',
     *          'queue_name2',
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    private array $queuesByTopic;

    /**
     * @param array $messageProcessorsByTopicAndQueue
     *  [
     *      'topic_name1' => [
     *          'queue_name1' => 'message_processor1',
     *          // ...
     *      ],
     *      // ...
     *  ]
     * @param array $queuesByTopic
     *  [
     *      'topic_name1' => [
     *          'queue_name1',
     *          'queue_name2',
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    public function __construct(array $queuesByTopic, array $messageProcessorsByTopicAndQueue)
    {
        $this->messageProcessorsByTopicAndQueue = $messageProcessorsByTopicAndQueue;
        $this->queuesByTopic = $queuesByTopic;
    }

    /**
     * @param string $topicName
     *
     * @return TopicMeta
     */
    public function getTopicMeta(string $topicName): TopicMeta
    {
        $topicName = strtolower($topicName);

        return new TopicMeta(
            $topicName,
            $this->queuesByTopic[$topicName] ?? [Config::DEFAULT_QUEUE_NAME],
            $this->messageProcessorsByTopicAndQueue[$topicName] ?? []
        );
    }

    /**
     * @return iterable<TopicMeta>
     */
    public function getTopicsMeta(): iterable
    {
        foreach (array_keys($this->queuesByTopic) as $topicName) {
            yield $this->getTopicMeta($topicName);
        }
    }
}
