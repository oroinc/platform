<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

use Oro\Bundle\ApiBundle\Config\Definition\SortersConfiguration;
use Oro\Bundle\ApiBundle\Config\Loader\SortersConfigLoader;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Adds "sorters" section to entity configuration.
 */
class SortersConfigExtension extends AbstractConfigExtension
{
    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationSections(): array
    {
        return [ConfigUtil::SORTERS => new SortersConfiguration()];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders(): array
    {
        return [ConfigUtil::SORTERS => new SortersConfigLoader()];
    }
}
