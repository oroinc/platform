<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\ApiBundle\Config\Definition\FiltersConfiguration;
use Oro\Bundle\ApiBundle\Config\Loader\FiltersConfigLoader;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Adds "filters" section to entity configuration.
 */
class FiltersConfigExtension extends AbstractConfigExtension
{
    /** @var FilterOperatorRegistry */
    private $filterOperatorRegistry;

    public function __construct(FilterOperatorRegistry $filterOperatorRegistry)
    {
        $this->filterOperatorRegistry = $filterOperatorRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationSections()
    {
        return [ConfigUtil::FILTERS => new FiltersConfiguration($this->filterOperatorRegistry)];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders()
    {
        return [ConfigUtil::FILTERS => new FiltersConfigLoader()];
    }
}
