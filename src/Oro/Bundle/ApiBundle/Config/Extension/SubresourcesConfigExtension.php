<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\ApiBundle\Config\Definition\SubresourcesConfiguration;
use Oro\Bundle\ApiBundle\Config\Loader\SubresourcesConfigLoader;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Adds "subresources" section to entity configuration.
 */
class SubresourcesConfigExtension extends AbstractConfigExtension
{
    private ActionProcessorBagInterface $actionProcessorBag;
    private FilterOperatorRegistry $filterOperatorRegistry;

    public function __construct(
        ActionProcessorBagInterface $actionProcessorBag,
        FilterOperatorRegistry $filterOperatorRegistry
    ) {
        $this->actionProcessorBag = $actionProcessorBag;
        $this->filterOperatorRegistry = $filterOperatorRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationSections(): array
    {
        return [
            ConfigUtil::SUBRESOURCES => new SubresourcesConfiguration(
                $this->actionProcessorBag->getActions(),
                $this->filterOperatorRegistry
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders(): array
    {
        return [ConfigUtil::SUBRESOURCES => new SubresourcesConfigLoader()];
    }
}
