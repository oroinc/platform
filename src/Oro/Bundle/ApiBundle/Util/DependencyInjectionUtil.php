<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Provides a set of static methods to simplify building of the service container.
 */
class DependencyInjectionUtil
{
    /** the name of DIC tag for API processors */
    public const PROCESSOR_TAG = 'oro.api.processor';

    /** the name of DIC parameter that is used to share ApiBundle configuration when building DIC */
    private const API_BUNDLE_CONFIG_PARAMETER_NAME = 'oro_api.bundle_config';

    private const REQUEST_TYPE = ApiContext::REQUEST_TYPE;

    /**
     * Returns the loaded and processed configuration of ApiBundle.
     */
    public static function getConfig(ContainerBuilder $container): array
    {
        return $container->getParameter(self::API_BUNDLE_CONFIG_PARAMETER_NAME);
    }

    /**
     * Updates the loaded and processed configuration of ApiBundle.
     * IMPORTANT: all updates must be performed in extensions of bundles
     * to be able to use updated configuration in compiler passes.
     */
    public static function setConfig(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(self::API_BUNDLE_CONFIG_PARAMETER_NAME, $config);
    }

    /**
     * Removes the loaded and processed configuration of ApiBundle.
     *
     * @internal never use this method outside of ApiBundle.
     */
    public static function removeConfig(ContainerBuilder $container): void
    {
        $parameterBag = $container->getParameterBag();
        $parameterBag->set(self::API_BUNDLE_CONFIG_PARAMETER_NAME, null);
        if ($parameterBag instanceof ParameterBag) {
            $parameterBag->remove(self::API_BUNDLE_CONFIG_PARAMETER_NAME);
        }
    }

    /**
     * Gets the specific service by its identifier or alias.
     */
    public static function findDefinition(ContainerBuilder $container, string $serviceId): ?Definition
    {
        return $container->hasDefinition($serviceId) || $container->hasAlias($serviceId)
            ? $container->findDefinition($serviceId)
            : null;
    }

    /**
     * Disables the specific API processor for the given request type.
     */
    public static function disableApiProcessor(
        ContainerBuilder $container,
        string $processorServiceId,
        string $requestType
    ): void {
        $processorDef = $container->getDefinition($processorServiceId);
        $tags = $processorDef->getTag(self::PROCESSOR_TAG);
        $processorDef->clearTag(self::PROCESSOR_TAG);

        foreach ($tags as $tag) {
            if (empty($tag[self::REQUEST_TYPE])) {
                $tag = self::addRequestTypeToTag($tag, '!' . $requestType);
            } else {
                $tag[self::REQUEST_TYPE] = sprintf('!%s&%s', $requestType, $tag[self::REQUEST_TYPE]);
            }
            $processorDef->addTag(self::PROCESSOR_TAG, $tag);
        }
    }

    private static function addRequestTypeToTag(array $tag, string $value): array
    {
        $extraAttrName = 'extra';
        if (\array_key_exists('extra', $tag)) {
            $attributes = [];
            foreach ($tag as $attrName => $attrVal) {
                $attributes[$attrName] = $attrVal;
                if ($attrName === $extraAttrName) {
                    $attributes[self::REQUEST_TYPE] = $value;
                }
            }
            $tag = $attributes;
        } else {
            $tag = [self::REQUEST_TYPE => $value] + $tag;
        }

        return $tag;
    }
}
