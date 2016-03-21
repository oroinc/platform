<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ActionsConfiguration;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ActionsConfigExtension extends AbstractConfigExtension
{
    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationSections()
    {
        return [ConfigUtil::ACTIONS => new ActionsConfiguration()];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders()
    {
        return [ConfigUtil::ACTIONS => new ActionsConfigLoader()];
    }
}
