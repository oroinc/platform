<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Component\EntitySerializer\ConfigConverter as BaseConfigConverter;
use Oro\Component\EntitySerializer\EntityConfig;

class ConfigConverter extends BaseConfigConverter
{
    /**
     * {@inheritdoc}
     */
    protected function buildEntityConfig(EntityConfig $result, array $config)
    {
        parent::buildEntityConfig($result, $config);

        if (!empty($config[EntityDefinitionConfig::PARENT_RESOURCE_CLASS])) {
            $result->set(AclProtectedQueryFactory::SKIP_ACL_FOR_ROOT_ENTITY, true);
        }
    }
}
