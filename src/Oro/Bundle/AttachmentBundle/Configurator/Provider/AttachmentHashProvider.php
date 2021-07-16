<?php

namespace Oro\Bundle\AttachmentBundle\Configurator\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Configurator\AttachmentFilterConfiguration;

/**
 * Responsible for build unique hash and involved in building the url hash and specifying the location of the
 * attachment storage.
 */
class AttachmentHashProvider
{
    /**
     * @var AttachmentPostProcessorsProvider
     */
    private $attachmentPostProcessorsProvider;

    /**
     * @var FilterConfiguration
     */
    private $attachmentFilterConfiguration;

    public function __construct(
        AttachmentPostProcessorsProvider $attachmentPostProcessorsProvider,
        AttachmentFilterConfiguration $attachmentFilterConfiguration
    ) {
        $this->attachmentPostProcessorsProvider = $attachmentPostProcessorsProvider;
        $this->attachmentFilterConfiguration = $attachmentFilterConfiguration;
    }

    /**
     * This method implements backward compatibility, which allows you to not change the hash to create the
     * attachment URL. For proper backward compatibility processing, we need to ensure that all previous decorators
     * are implemented.
     *
     * In this case, we have a service decorator that can return both the new filter and the original,
     * depending on the configuration.
     */
    public function getFilterConfigHash(string $filterName): string
    {
        $filterConfig = $this->attachmentPostProcessorsProvider->isPostProcessingEnabled()
            ? $this->attachmentFilterConfiguration->get($filterName)
            : $this->attachmentFilterConfiguration->getOriginal($filterName);

        return md5(json_encode($filterConfig));
    }
}
