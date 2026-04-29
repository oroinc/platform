<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents a webhook notification event within the system.
 *
 * This event is triggered to notify external systems via webhooks.
 * It carries information about the topic and the associated event data.
 */
class WebhookNotifyEvent extends Event
{
    public const string NAME = 'oro_integration.webhook_notify';

    public function __construct(
        private string $topic,
        private array $eventData
    ) {
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function getEventData(): array
    {
        return $this->eventData;
    }
}
