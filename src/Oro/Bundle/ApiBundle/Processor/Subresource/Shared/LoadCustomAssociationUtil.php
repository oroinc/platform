<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\TargetConfigExtraBuilder;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;

/**
 * Provides static methods to simplify loading data for custom associations.
 */
final class LoadCustomAssociationUtil
{
    /**
     * Builds a configuration for a parent entity that should be used to load data for a custom association.
     */
    public static function buildParentEntityConfig(
        SubresourceContext $context,
        ConfigProvider $configProvider
    ): EntityDefinitionConfig {
        $parentClassName = $context->getParentClassName();
        $associationName = $context->getAssociationName();

        $configExtras = TargetConfigExtraBuilder::buildParentConfigExtras(
            $context->getConfigExtras(),
            $parentClassName,
            $associationName
        );
        $config = $configProvider
            ->getConfig($parentClassName, $context->getVersion(), $context->getRequestType(), $configExtras)
            ->getDefinition();
        TargetConfigExtraBuilder::normalizeParentConfig($config, $associationName, $configExtras);

        return $config;
    }
}
