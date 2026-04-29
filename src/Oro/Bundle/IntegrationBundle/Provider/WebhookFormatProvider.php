<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

/**
 * A provider class for managing webhook format mappings.
 */
class WebhookFormatProvider
{
    private array $formats = [];

    public function addFormat(string $key, string $label): void
    {
        $this->formats[$key] = $label;
    }

    /**
     * @return array [key => non-translated label, ...]
     */
    public function getFormats(): array
    {
        return $this->formats;
    }
}
