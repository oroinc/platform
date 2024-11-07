<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\TargetConfigExtraBuilder;
use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;

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
        if ($context->getAction() === ApiAction::GET_SUBRESOURCE
            && !is_a($context->getClassName(), EntityIdentifier::class, true)
        ) {
            $i = self::findExpandRelatedEntitiesConfigExtraKey($configExtras);
            if (null === $i) {
                $configExtras[] = new ExpandRelatedEntitiesConfigExtra([$associationName]);
            } else {
                /** @var ExpandRelatedEntitiesConfigExtra $expandRelatedEntitiesConfigExtra */
                $expandRelatedEntitiesConfigExtra = $configExtras[$i];
                if (!$expandRelatedEntitiesConfigExtra->isExpandRequested($associationName)) {
                    $configExtras[$i] = new ExpandRelatedEntitiesConfigExtra(array_merge(
                        $expandRelatedEntitiesConfigExtra->getExpandedEntities(),
                        [$associationName]
                    ));
                }
            }
        }
        $config = $configProvider
            ->getConfig($parentClassName, $context->getVersion(), $context->getRequestType(), $configExtras)
            ->getDefinition();
        TargetConfigExtraBuilder::normalizeParentConfig($config, $associationName, $configExtras);

        return $config;
    }

    private static function findExpandRelatedEntitiesConfigExtraKey(array $configExtras): ?int
    {
        foreach ($configExtras as $i => $extra) {
            if ($extra instanceof ExpandRelatedEntitiesConfigExtra) {
                return $i;
            }
        }

        return null;
    }
}
