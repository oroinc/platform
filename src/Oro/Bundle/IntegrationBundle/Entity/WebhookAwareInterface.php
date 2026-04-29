<?php

namespace Oro\Bundle\IntegrationBundle\Entity;

/**
 * Represents entity aware about workflow
 */
interface WebhookAwareInterface
{
    public function getWebhook(): ?WebhookConsumerSettings;
    public function setWebhook(?WebhookConsumerSettings $webhook): self;
}
