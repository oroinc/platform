<?php

namespace Oro\Bundle\AttachmentBundle\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Provider\WebpAwareFilterRuntimeConfigProvider;

/**
 * Provides runtime configuration for image format conversion,
 * with special handling for WebP format including quality settings.
 *
 * This provider adapts the existing WebpAwareFilterRuntimeConfigProvider
 * to work with the RuntimeConfigProviderInterface.
 */
class RuntimeWebpFormatConfigurationProvider implements RuntimeConfigProviderInterface
{
    private WebpAwareFilterRuntimeConfigProvider $webpAwareProvider;

    public function __construct(WebpAwareFilterRuntimeConfigProvider $webpAwareProvider)
    {
        $this->webpAwareProvider = $webpAwareProvider;
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

        // Use existing providers to get runtime configuration
        return $this->webpAwareProvider->getRuntimeConfigForFilter($filter, $format);
    }
}
