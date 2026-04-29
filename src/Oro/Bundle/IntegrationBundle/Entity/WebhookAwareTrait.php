<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

/**
 * Represents an entity aware of local webhook relation.
 */
trait WebhookAwareTrait
{
    public function getWebhook(): ?WebhookConsumerSettings
    {
        return $this->webhook;
    }

    public function setWebhook(?WebhookConsumerSettings $webhook): self
    {
        $this->webhook = $webhook;

        return $this;
    }
}
