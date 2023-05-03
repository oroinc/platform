<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\ApiBundle\Config\Definition\ActionsConfiguration;
use Oro\Bundle\ApiBundle\Config\Loader\ActionsConfigLoader;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Adds "actions" section to entity configuration.
 */
class ActionsConfigExtension extends AbstractConfigExtension
{
    protected ActionProcessorBagInterface $actionProcessorBag;

    public function __construct(ActionProcessorBagInterface $actionProcessorBag)
    {
        $this->actionProcessorBag = $actionProcessorBag;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationSections(): array
    {
        return [ConfigUtil::ACTIONS => new ActionsConfiguration($this->actionProcessorBag->getActions())];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders(): array
    {
        return [ConfigUtil::ACTIONS => new ActionsConfigLoader()];
    }
}
