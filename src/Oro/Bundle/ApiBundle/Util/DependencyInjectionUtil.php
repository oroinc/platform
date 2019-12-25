<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\ApiBundle\Processor\ApiContext;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Provides a set of static methods to simplify building of the service container.
 */
class DependencyInjectionUtil
{
    /** the name of DIC tag for API processors */
    public const PROCESSOR_TAG = 'oro.api.processor';

    /** the attribute to specify the request type for "oro.api.processor" DIC tag */
    public const REQUEST_TYPE = ApiContext::REQUEST_TYPE;

    /** the name of DIC parameter that is used to share ApiBundle configuration during building of DIC */
    private const API_BUNDLE_CONFIG_PARAMETER_NAME = 'oro_api.bundle_config';

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
    public static function setConfig(ContainerBuilder $container, array $config): void
    {
        $container->setParameter(self::API_BUNDLE_CONFIG_PARAMETER_NAME, $config);
    }

    /**
     * Removes the loaded and processed configuration of ApiBundle.
     * @internal never use this method outside of ApiBundle.
     *
     * @param ContainerBuilder $container
     *
     * @return array
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
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     *
     * @return Definition|null
     */
    public static function findDefinition(ContainerBuilder $container, string $serviceId): ?Definition
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
    public static function getAttribute(array $attributes, string $attributeName, $defaultValue)
    {
        return array_key_exists($attributeName, $attributes)
            ? $attributes[$attributeName]
            : $defaultValue;
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
    public static function getRequiredAttribute(
        array $attributes,
        string $attributeName,
        string $serviceId,
        string $tagName
    ) {
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
    public static function getPriority(array $attributes): int
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
    public static function getRequestType(array $attributes): ?string
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
    public static function sortByPriorityAndFlatten(array $services): array
    {
        krsort($services);

        return array_merge(...$services);
    }

    /**
     * Registers tagged services that depend on the request type.
     *
     * @param ContainerBuilder $container
     * @param string           $chainServiceId
     * @param string           $tagName
     */
    public static function registerRequestTypeDependedTaggedServices(
        ContainerBuilder $container,
        string $chainServiceId,
        string $tagName
    ): void {
        $services = [];
        $items = [];
        $taggedServices = $container->findTaggedServiceIds($tagName);
        foreach ($taggedServices as $id => $attributes) {
            $services[$id] = new Reference($id);
            foreach ($attributes as $tagAttributes) {
                $items[self::getPriority($tagAttributes)][] = [$id, self::getRequestType($tagAttributes)];
            }
        }
        if ($items) {
            $items = self::sortByPriorityAndFlatten($items);
        }

        $container->getDefinition($chainServiceId)
            ->replaceArgument(0, $items)
            ->replaceArgument(1, ServiceLocatorTagPass::register($container, $services));
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

    /**
     * @param array  $tag
     * @param string $value
     *
     * @return array
     */
    private static function addRequestTypeToTag(array $tag, string $value): array
    {
        $extraAttrName = 'extra';
        if (array_key_exists('extra', $tag)) {
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
