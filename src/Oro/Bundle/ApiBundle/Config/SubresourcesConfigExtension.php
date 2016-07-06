<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\SubresourcesConfiguration;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class SubresourcesConfigExtension extends AbstractConfigExtension
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
        return [ConfigUtil::SUBRESOURCES => new SubresourcesConfiguration($this->actionProcessorBag->getActions())];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders()
    {
        return [ConfigUtil::SUBRESOURCES => new SubresourcesConfigLoader()];
    }
}
