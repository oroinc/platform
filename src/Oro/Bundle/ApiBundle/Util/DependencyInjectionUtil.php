<?php

namespace Oro\Bundle\ApiBundle\Util;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Provides a set of methods to simplify working with the service container.
 */
class DependencyInjectionUtil
{
    /** the name of DIC tag for Data API processors */
    public const PROCESSOR_TAG = 'oro.api.processor';

    /** the attribute to specify the request type for "oro.api.processor" DIC tag */
    public const REQUEST_TYPE = 'requestType';

    /**
     * @internal never use this constant outside of ApiBundle,
     *           to receive and update the configuration use getConfig and setConfig methods.
     */
    public const API_BUNDLE_CONFIG_PARAMETER_NAME = 'oro_api.bundle_config';

    /**
     * Returns the loaded and processed configuration of ApiBundle.
     *
     * @param ContainerBuilder $container
     *
     * @return array
     */
    public static function getConfig(ContainerBuilder $container): array
    {
        return $container->getParameter(self::API_BUNDLE_CONFIG_PARAMETER_NAME);
    }

    /**
     * Updates the loaded and processed configuration of ApiBundle.
     * IMPORTANT: all updates must be performed in extensions of bundles
     * to be able to use updated configuration in compiler passes.
     *
     * @param ContainerBuilder $container
     * @param array $config
     */
    public static function setConfig(ContainerBuilder $container, array $config)
    {
        $container->setParameter(self::API_BUNDLE_CONFIG_PARAMETER_NAME, $config);
    }

    /**
     * Gets the specific service by its identifier or alias.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     *
     * @return Definition|null
     */
    public static function findDefinition(ContainerBuilder $container, $serviceId)
    {
        return $container->hasDefinition($serviceId) || $container->hasAlias($serviceId)
            ? $container->findDefinition($serviceId)
            : null;
    }

    /**
     * Gets a value of the specific tag attribute.
     *
     * @param array  $attributes
     * @param string $attributeName
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public static function getAttribute(array $attributes, $attributeName, $defaultValue)
    {
        if (!array_key_exists($attributeName, $attributes)) {
            return $defaultValue;
        }

        return $attributes[$attributeName];
    }

    /**
     * Gets a value of the specific mandatory tag attribute.
     *
     * @param array  $attributes
     * @param string $attributeName
     * @param string $serviceId
     * @param string $tagName
     *
     * @return mixed
     *
     * @throws LogicException is the requested attribute does not exist in $attributes array
     */
    public static function getRequiredAttribute(array $attributes, $attributeName, $serviceId, $tagName)
    {
        if (!array_key_exists($attributeName, $attributes)) {
            throw new LogicException(sprintf(
                'The attribute "%s" is mandatory for "%s" tag. Service: "%s".',
                $attributeName,
                $tagName,
                $serviceId
            ));
        }

        return $attributes[$attributeName];
    }

    /**
     * Gets a value of the "priority" attribute.
     * If a tag does not have this attribute, 0 is returned.
     *
     * @param array $attributes
     *
     * @return int
     */
    public static function getPriority(array $attributes)
    {
        return self::getAttribute($attributes, 'priority', 0);
    }

    /**
     * Gets a value of the "requestType" attribute.
     *
     * @param array $attributes
     *
     * @return string|null
     */
    public static function getRequestType(array $attributes)
    {
        return self::getAttribute($attributes, self::REQUEST_TYPE, null);
    }

    /**
     * Sorts the tagged services by the priority;
     * the higher the priority, the earlier element is added to the result list,
     * and return flatten array of sorted services.
     *
     * @param array $services [priority => item, ...]
     *
     * @return array [item, ...]
     */
    public static function sortByPriorityAndFlatten(array $services)
    {
        krsort($services);

        return array_merge(...$services);
    }

    /**
     * Registers tagged services.
     *
     * @param ContainerBuilder $container
     * @param string           $chainServiceId
     * @param string           $tagName
     * @param string           $addMethodName
     */
    public static function registerTaggedServices(
        ContainerBuilder $container,
        $chainServiceId,
        $tagName,
        $addMethodName
    ) {
        $chainServiceDef = self::findDefinition($container, $chainServiceId);
        if (null !== $chainServiceDef) {
            // find services
            $services = [];
            $taggedServices = $container->findTaggedServiceIds($tagName);
            foreach ($taggedServices as $id => $attributes) {
                foreach ($attributes as $tagAttributes) {
                    $services[self::getPriority($tagAttributes)][] = new Reference($id);
                }
            }
            if (empty($services)) {
                return;
            }

            // sort by priority and flatten
            $services = self::sortByPriorityAndFlatten($services);

            // register
            foreach ($services as $service) {
                $chainServiceDef->addMethodCall($addMethodName, [$service]);
            }
        }
    }

    /**
     * Disables the specific API processor for the given request type.
     *
     * @param ContainerBuilder $container
     * @param string           $processorServiceId
     * @param string           $requestType
     */
    public static function disableApiProcessor(
        ContainerBuilder $container,
        string $processorServiceId,
        string $requestType
    ) {
        $processorDef = $container->getDefinition($processorServiceId);
        $tags = $processorDef->getTag(self::PROCESSOR_TAG);
        $processorDef->clearTag(self::PROCESSOR_TAG);

        foreach ($tags as $tag) {
            if (empty($tag[self::REQUEST_TYPE])) {
                $tag[self::REQUEST_TYPE] = '!' . $requestType;
            } else {
                $tag[self::REQUEST_TYPE] = sprintf('!%s&%s', $requestType, $tag[self::REQUEST_TYPE]);
            }
            $processorDef->addTag(self::PROCESSOR_TAG, $tag);
        }
    }
}
