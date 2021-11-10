<?php

namespace Oro\Component\MessageQueue\Client\Meta;

/**
 * Provides description for message queue topics.
 */
class TopicDescriptionProvider
{
    /**
     * @var array
     *  [
     *      'topic_name1' => 'Topic description 1',
     *      'topic_name2' => 'Topic description 2',
     *      // ...
     *  ]
     */
    private array $topicDescriptions;

    public function __construct(array $topicDescriptions)
    {
        $this->topicDescriptions = $topicDescriptions;
    }

    public function getTopicDescription(string $topicName): string
    {
        return $this->topicDescriptions[strtolower($topicName)] ?? '';
    }
}
