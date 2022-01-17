<?php

namespace Oro\Component\MessageQueue\Client\Meta;

use Oro\Component\MessageQueue\Topic\TopicRegistry;

/**
 * Provides description for message queue topics.
 */
class TopicDescriptionProvider
{
    private TopicRegistry $topicRegistry;

    public function __construct(TopicRegistry $topicRegistry)
    {
        $this->topicRegistry = $topicRegistry;
    }

    public function getTopicDescription(string $topicName): string
    {
        $topicName = strtolower($topicName);
        if ($this->topicRegistry->has($topicName)) {
            return $this->topicRegistry->get($topicName)->getDescription();
        }

        return '';
    }
}
