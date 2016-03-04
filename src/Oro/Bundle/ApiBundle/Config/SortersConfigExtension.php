<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\SortersConfiguration;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class SortersConfigExtension extends AbstractConfigExtension
{
    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationSections()
    {
        return [ConfigUtil::SORTERS => new SortersConfiguration()];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders()
    {
        return [ConfigUtil::SORTERS => new SortersConfigLoader()];
    }
}
