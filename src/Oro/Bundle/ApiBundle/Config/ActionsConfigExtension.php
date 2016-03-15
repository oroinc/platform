<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ActionsConfiguration;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

class ActionsConfigExtension extends AbstractConfigExtension
{
    public function getEntityConfigurationSections()
    {
        return ['actions' => new ActionsConfiguration()];
    }

    public function getEntityConfigurationLoaders()
    {
        return ['actions' => new ActionsConfigLoader()];
    }
}
