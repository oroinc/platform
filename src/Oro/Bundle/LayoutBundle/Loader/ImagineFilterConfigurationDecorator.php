<?php

namespace Oro\Bundle\LayoutBundle\Loader;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Decorates Imagine's filter configuration to load custom filters configuration on demand.
 */
class ImagineFilterConfigurationDecorator extends FilterConfiguration
{
    /**
     * @var FilterConfiguration
     */
    private $filterConfiguration;

    /**
     * @var ServiceLink
     */
    private $filterLoaderServiceLink;

    public function __construct(FilterConfiguration $filterConfiguration, ServiceLink $filterLoaderServiceLink)
    {
        $this->filterConfiguration = $filterConfiguration;
        $this->filterLoaderServiceLink = $filterLoaderServiceLink;
    }

    #[\Override]
    public function get($filter)
    {
        $this->filterLoaderServiceLink->getService()->load();

        return $this->filterConfiguration->get($filter);
    }

    #[\Override]
    public function set($filter, array $config)
    {
        return $this->filterConfiguration->set($filter, $config);
    }

    #[\Override]
    public function all()
    {
        $this->filterLoaderServiceLink->getService()->load();

        return $this->filterConfiguration->all();
    }
}
