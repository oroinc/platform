<?php

namespace Oro\Bundle\AttachmentBundle\Configurator;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Configurator\Provider\AttachmentPostProcessorsProvider;

/**
 * Attachment filter configurator. Adds a "post-processor" configuration to the filter config
 * (liip_imagine or theme dimensions).
 */
class AttachmentFilterConfiguration extends FilterConfiguration
{
    /**
     * @var AttachmentPostProcessorsProvider
     */
    private $attachmentPostProcessorsProvider;

    /**
     * @var FilterConfiguration
     */
    private $filterConfiguration;

    public function __construct(
        FilterConfiguration $filterConfiguration,
        AttachmentPostProcessorsProvider $attachmentPostProcessorsProvider
    ) {
        $this->filterConfiguration = $filterConfiguration;
        $this->attachmentPostProcessorsProvider = $attachmentPostProcessorsProvider;
    }

    /**
     * @param string $filter
     *
     * @return array
     */
    public function get($filter): array
    {
        $config = $this->filterConfiguration->get($filter);

        return $this->addProcessorsConfig($config);
    }

    /**
     * @param string $filter
     *
     * @return array
     */
    public function getOriginal($filter): array
    {
        return $this->filterConfiguration->get($filter);
    }

    /**
     * @param string $filter
     * @param array $config
     */
    public function set($filter, array $config): void
    {
        $this->filterConfiguration->set($filter, $config);
    }

    public function all(): array
    {
        return array_map(
            function (array $config) {
                return $this->addProcessorsConfig($config);
            },
            $this->filterConfiguration->all()
        );
    }

    private function addProcessorsConfig(array $config = []): array
    {
        // Default processors configuration takes precedence over system settings.
        if (empty($config['post_processors'])) {
            $config['post_processors'] = $this->attachmentPostProcessorsProvider->getFilterConfig();
        }

        return $config;
    }
}
