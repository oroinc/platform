<?php

namespace Oro\Bundle\AttachmentBundle\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Configurator\AttachmentFilterConfiguration;

/**
 * Responsible for build unique hash and involved in building the url hash and specifying the location of the
 * attachment storage.
 */
class AttachmentHashProvider
{
    private AttachmentPostProcessorsProvider $attachmentPostProcessorsProvider;

    private AttachmentFilterConfiguration $attachmentFilterConfiguration;

    private RuntimeConfigurationProvider $runtimeConfigurationProvider;

    public function __construct(
        AttachmentPostProcessorsProvider $attachmentPostProcessorsProvider,
        AttachmentFilterConfiguration $attachmentFilterConfiguration,
        RuntimeConfigurationProvider $runtimeConfigurationProvider
    ) {
        $this->attachmentPostProcessorsProvider = $attachmentPostProcessorsProvider;
        $this->attachmentFilterConfiguration = $attachmentFilterConfiguration;
        $this->runtimeConfigurationProvider = $runtimeConfigurationProvider;
    }

    /**
     * This method implements backward compatibility, which allows you to not change the hash to create the
     * attachment URL. For proper backward compatibility processing, we need to ensure that all previous decorators
     * are implemented.
     *
     * In this case, we have a service decorator that can return both the new filter and the original,
     * depending on the configuration.
     */
    public function getFilterConfigHash(string $filterName, string $format = ''): string
    {
        $filterConfig = $this->attachmentPostProcessorsProvider->isPostProcessingEnabled()
            ? $this->attachmentFilterConfiguration->get($filterName)
            : $this->attachmentFilterConfiguration->getOriginal($filterName);

        $filterConfig = array_replace_recursive(
            $filterConfig,
            $this->runtimeConfigurationProvider->getRuntimeConfig(
                $filterName,
                [
                    // Tells RuntimeMetadataConfigurationProvider to include metadata post-processor config in the hash
                    // without requiring original_content. This ensures the URL hash changes when metadata
                    // preservation settings are toggled, invalidating cached images.
                    'metadata_refresh_hash' => true,
                    // Specifies target image format (e.g., 'webp', 'jpg', 'png'). Used by
                    // RuntimeWebpFormatConfigurationProvider to add format-specific settings to filter config
                    // (e.g., quality for WebP). Different formats produce different URL hashes, enabling
                    // separate cached versions of the same image in multiple formats.
                    'format' => $format
                ]
            )
        );

        return md5(json_encode($filterConfig));
    }
}
