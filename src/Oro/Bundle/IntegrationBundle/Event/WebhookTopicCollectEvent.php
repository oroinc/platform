<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Oro\Bundle\IntegrationBundle\Model\WebhookTopic;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents an event that is triggered to collect webhook topics.
 *
 * This event allows for the registration and retrieval of webhook topics.
 * Listeners can utilize this event to add their own topics to the collection.
 */
class WebhookTopicCollectEvent extends Event
{
    public const NAME = 'oro_integration.webhook_topic_collect';

    /**
     * @param WebhookTopic[] $topics
     */
    public function __construct(
        private array $topics
    ) {
    }

    /**
     * @return WebhookTopic[] [topic name => topic, ...]
     */
    public function getTopics(): array
    {
        return $this->topics;
    }

    public function addTopic(WebhookTopic $topic): void
    {
        $this->topics[$topic->getName()] = $topic;
    }
}
