<?php

namespace Oro\Bundle\AttachmentBundle\Configurator\Provider;

use Oro\Bundle\AttachmentBundle\Configurator\AttachmentFilterConfiguration;
use Oro\Bundle\AttachmentBundle\Provider\FilterRuntimeConfigProviderInterface;

/**
 * Responsible for build unique hash and involved in building the url hash and specifying the location of the
 * attachment storage.
 */
class AttachmentHashProvider
{
    private AttachmentPostProcessorsProvider $attachmentPostProcessorsProvider;

    private AttachmentFilterConfiguration $attachmentFilterConfiguration;

    private FilterRuntimeConfigProviderInterface $filterRuntimeConfigProvider;

    public function __construct(
        AttachmentPostProcessorsProvider $attachmentPostProcessorsProvider,
        AttachmentFilterConfiguration $attachmentFilterConfiguration,
        FilterRuntimeConfigProviderInterface $filterRuntimeConfigProvider
    ) {
        $this->attachmentPostProcessorsProvider = $attachmentPostProcessorsProvider;
        $this->attachmentFilterConfiguration = $attachmentFilterConfiguration;
        $this->filterRuntimeConfigProvider = $filterRuntimeConfigProvider;
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
            $this->filterRuntimeConfigProvider->getRuntimeConfigForFilter($filterName, $format)
        );

        return md5(json_encode($filterConfig));
    }
}
