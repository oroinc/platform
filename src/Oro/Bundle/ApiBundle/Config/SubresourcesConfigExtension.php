<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\SubresourcesConfiguration;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Adds "subresources" section to entity configuration.
 */
class SubresourcesConfigExtension extends AbstractConfigExtension
{
    /** @var ActionProcessorBagInterface */
    private $actionProcessorBag;

    /** @var FilterOperatorRegistry */
    private $filterOperatorRegistry;

    /**
     * @param ActionProcessorBagInterface $actionProcessorBag
     * @param FilterOperatorRegistry      $filterOperatorRegistry
     */
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
    public function getEntityConfigurationSections()
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
    public function getEntityConfigurationLoaders()
    {
        return [ConfigUtil::SUBRESOURCES => new SubresourcesConfigLoader()];
    }
}
