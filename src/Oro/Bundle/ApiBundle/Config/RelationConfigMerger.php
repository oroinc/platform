<?php

namespace Oro\Bundle\ApiBundle\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\RelationDefinitionConfiguration;

/**
 * Provides functionality to merge two configurations loaded from
 * "relations" section of "Resources/config/oro/api.yml".
 */
class RelationConfigMerger extends EntityConfigMerger
{
    /**
     * {@inheritdoc}
     */
    protected function getConfigurationSectionName()
    {
        return ApiConfiguration::RELATIONS_SECTION;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConfigurationSection()
    {
        return new RelationDefinitionConfiguration();
    }
}
