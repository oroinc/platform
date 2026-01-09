<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\Extra\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
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
            $associationName
        );
        if ($context->getAction() === ApiAction::GET_SUBRESOURCE) {
            if (!is_a($context->getClassName(), EntityIdentifier::class, true)) {
                self::processExpandRelatedEntitiesConfigExtra($configExtras, $associationName);
            }
            self::processFilterFieldsConfigExtraConfigExtra($configExtras, $parentClassName, $associationName);
        }
        $config = $configProvider
            ->getConfig($parentClassName, $context->getVersion(), $context->getRequestType(), $configExtras)
            ->getDefinition();
        TargetConfigExtraBuilder::normalizeParentConfig($config, $associationName, $configExtras);

        return $config;
    }

    private static function processExpandRelatedEntitiesConfigExtra(
        array &$configExtras,
        string $associationName
    ): void {
        $i = self::findConfigExtraKey($configExtras, ExpandRelatedEntitiesConfigExtra::class);
        if (null === $i) {
            $configExtras[] = new ExpandRelatedEntitiesConfigExtra([$associationName]);
        } else {
            /** @var ExpandRelatedEntitiesConfigExtra $expandRelatedEntitiesConfigExtra */
            $expandRelatedEntitiesConfigExtra = $configExtras[$i];
            if (!$expandRelatedEntitiesConfigExtra->isExpandRequested($associationName)) {
                $expandedEntities = $expandRelatedEntitiesConfigExtra->getExpandedEntities();
                $expandedEntities[] = $associationName;
                $configExtras[$i] = new ExpandRelatedEntitiesConfigExtra($expandedEntities);
            }
        }
    }

    private static function processFilterFieldsConfigExtraConfigExtra(
        array &$configExtras,
        string $parentClassName,
        string $associationName
    ): void {
        $i = self::findConfigExtraKey($configExtras, FilterFieldsConfigExtra::class);
        if (null === $i) {
            $configExtras[] = new FilterFieldsConfigExtra([$parentClassName => [$associationName]]);
        } else {
            /** @var FilterFieldsConfigExtra $filterFieldsConfigExtra */
            $filterFieldsConfigExtra = $configExtras[$i];
            $fieldFilters = $filterFieldsConfigExtra->getFieldFilters();
            if (
                !\array_key_exists($parentClassName, $fieldFilters)
                || !\in_array($associationName, $fieldFilters[$parentClassName] ?? [], true)
            ) {
                $fieldFilters[$parentClassName][] = $associationName;
                $configExtras[$i] = new FilterFieldsConfigExtra($fieldFilters);
            }
        }
    }

    private static function findConfigExtraKey(array $configExtras, string $configExtraClass): ?int
    {
        foreach ($configExtras as $i => $extra) {
            if ($extra instanceof $configExtraClass) {
                return $i;
            }
        }

        return null;
    }
}
