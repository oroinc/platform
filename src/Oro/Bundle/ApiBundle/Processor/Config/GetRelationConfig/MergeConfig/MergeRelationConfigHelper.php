<?php

namespace Oro\Bundle\ApiBundle\Processor\Config\GetRelationConfig\MergeConfig;

use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\RelationDefinitionConfiguration;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeEntityConfigHelper;

class MergeRelationConfigHelper extends MergeEntityConfigHelper
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
