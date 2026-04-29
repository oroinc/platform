<?php

namespace Oro\Bundle\IntegrationBundle\Api\Model;

/**
 * Represents a webhook topic.
 */
final readonly class WebhookTopic
{
    public function __construct(
        private string $id,
        private string $label
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
