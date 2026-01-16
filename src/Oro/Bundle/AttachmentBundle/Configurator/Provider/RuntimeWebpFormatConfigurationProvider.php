<?php

namespace Oro\Bundle\AttachmentBundle\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

/**
 * Provides runtime configuration for image format conversion,
 * with special handling for WebP format including quality settings.
 */
class RuntimeWebpFormatConfigurationProvider implements RuntimeConfigProviderInterface
{
    private WebpConfiguration $webpConfiguration;

    public function __construct(WebpConfiguration $webpConfiguration)
    {
        $this->webpConfiguration = $webpConfiguration;
    }

    public function isSupported(string $filter): bool
    {
        return true;
    }

    /**
     * Runtime config - adds format from context with format-specific settings.
     */
    public function getRuntimeConfig(string $filter, RuntimeContext $context): array
    {
        if (!$context->offsetExists('format')) {
            return [];
        }

        $format = $context->offsetGet('format');
        if (!$format) {
            return [];
        }

        $format = strtolower($format);
        $config = ['format' => $format];
        if ($format === 'webp' && !$this->webpConfiguration->isDisabled()) {
            $config['quality'] = $this->webpConfiguration->getWebpQuality();
        }

        return $config;
    }
}
