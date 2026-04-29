<?php

namespace Oro\Bundle\IntegrationBundle\Api\Repository;

use Oro\Bundle\IntegrationBundle\Api\Model\WebhookTopic;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;

/**
 * The repository to get available webhook topics.
 */
class WebhookTopicRepository
{
    public function __construct(
        private readonly WebhookConfigurationProvider $webhookConfigurationProvider
    ) {
    }

    /**
     * Returns all available webhook topics.
     *
     * @return WebhookTopic[]
     */
    public function getWebhookTopics(): array
    {
        $result = [];
        $topics = $this->webhookConfigurationProvider->getAvailableTopics();
        foreach ($topics as $topic) {
            $result[] = new WebhookTopic($topic->getName(), $topic->getLabel());
        }

        return $result;
    }

    /**
     * Gets a webhook topic by its ID if it is one of the available webhook topics.
     */
    public function findWebhookTopic(string $id): ?WebhookTopic
    {
        $topics = $this->webhookConfigurationProvider->getAvailableTopics();
        if (!isset($topics[$id])) {
            return null;
        }

        return new WebhookTopic($topics[$id]->getName(), $topics[$id]->getLabel());
    }
}
