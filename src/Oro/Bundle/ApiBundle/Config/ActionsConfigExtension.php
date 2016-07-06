<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ActionsConfiguration;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ActionsConfigExtension extends AbstractConfigExtension
{
    /** @var ActionProcessorBagInterface */
    protected $actionProcessorBag;

    /**
     * @param ActionProcessorBagInterface $actionProcessorBag
     */
    public function __construct(ActionProcessorBagInterface $actionProcessorBag)
    {
        $this->actionProcessorBag = $actionProcessorBag;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationSections()
    {
        return [ConfigUtil::ACTIONS => new ActionsConfiguration($this->actionProcessorBag->getActions())];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders()
    {
        return [ConfigUtil::ACTIONS => new ActionsConfigLoader()];
    }
}
