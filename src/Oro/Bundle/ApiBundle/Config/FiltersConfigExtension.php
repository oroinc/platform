<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\FiltersConfiguration;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class FiltersConfigExtension extends AbstractConfigExtension
{
    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationSections()
    {
        return [ConfigUtil::FILTERS => new FiltersConfiguration()];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders()
    {
        return [ConfigUtil::FILTERS => new FiltersConfigLoader()];
    }
}
