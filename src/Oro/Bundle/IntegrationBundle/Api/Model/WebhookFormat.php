<?php

namespace Oro\Bundle\IntegrationBundle\Api\Model;

/**
 * Represents a webhook format.
 */
final readonly class WebhookFormat
{
    public function __construct(
        private string $key,
        private string $label
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
