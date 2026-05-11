<?php

namespace Oro\Bundle\IntegrationBundle\Model;

/**
 * Represents a webhook topic, acts as a model to encapsulate the details of a webhook communication.
 */
final readonly class WebhookTopic
{
    public function __construct(
        private string $name,
        private string $label,
        private array $metadata = []
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
