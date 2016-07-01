<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\SubresourcesConfiguration;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class SubresourcesConfigExtension extends AbstractConfigExtension
{
    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationSections()
    {
        return [ConfigUtil::SUBRESOURCES => new SubresourcesConfiguration()];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders()
    {
        return [ConfigUtil::SUBRESOURCES => new SubresourcesConfigLoader()];
    }
}
