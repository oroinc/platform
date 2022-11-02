<?php

namespace Oro\Bundle\AttachmentBundle\Provider;

use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;

/**
 * LiipImagine filter runtime config provider that adds options to enable conversion to webp.
 */
class WebpAwareFilterRuntimeConfigProvider implements FilterRuntimeConfigProviderInterface
{
    private FilterRuntimeConfigProviderInterface $innerFilterRuntimeConfigProvider;

    private WebpConfiguration $webpConfiguration;

    public function __construct(
        FilterRuntimeConfigProviderInterface $innerFilterRuntimeConfigProvider,
        WebpConfiguration $webpConfiguration
    ) {
        $this->innerFilterRuntimeConfigProvider = $innerFilterRuntimeConfigProvider;
        $this->webpConfiguration = $webpConfiguration;
    }

    public function getRuntimeConfigForFilter(string $filterName, string $format = ''): array
    {
        $runtimeConfig = $this->innerFilterRuntimeConfigProvider->getRuntimeConfigForFilter($filterName, $format);

        return array_replace_recursive($runtimeConfig, $this->getRuntimeConfig($format));
    }

    private function getRuntimeConfig(string $format = ''): array
    {
        if ($format === 'webp' && !$this->webpConfiguration->isDisabled()) {
            return [
                'format' => 'webp',
                'quality' => $this->webpConfiguration->getWebpQuality(),
            ];
        }

        return [];
    }
}
