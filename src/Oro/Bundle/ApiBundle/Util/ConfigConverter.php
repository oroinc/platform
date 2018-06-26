<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Component\EntitySerializer\ConfigConverter as BaseConfigConverter;
use Oro\Component\EntitySerializer\EntityConfig;

/**
 * Provides a method to convert normalized configuration of the EntityConfig object.
 */
class ConfigConverter extends BaseConfigConverter
{
    /**
     * {@inheritdoc}
     */
    protected function buildEntityConfig(EntityConfig $result, array $config)
    {
        parent::buildEntityConfig($result, $config);

        if (!empty($config[ConfigUtil::PARENT_RESOURCE_CLASS])) {
            $result->set(AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY, true);
        }
    }
}
