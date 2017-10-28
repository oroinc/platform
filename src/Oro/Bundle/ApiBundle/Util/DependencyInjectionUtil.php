<?php

namespace Oro\Bundle\ApiBundle\Util;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\DependencyInjection\Configuration;

/**
 * Provides a set of methods to simplify working with the service container.
 */
class DependencyInjectionUtil
{
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
     * Gets configuration of ApiBundle.
     *
     * @param ContainerBuilder $container
     *
     * @return array
     */
    public static function getConfig(ContainerBuilder $container)
    {
        $processor = new Processor();

        return $processor->processConfiguration(
            new Configuration(),
            $container->getExtensionConfig('oro_api')
        );
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

        return call_user_func_array('array_merge', $services);
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
     * Replaces a regular service with the debug one.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     * @param string           $debugServiceClassName
     */
    public static function registerDebugService(
        ContainerBuilder $container,
        $serviceId,
        $debugServiceClassName = 'Oro\Component\ChainProcessor\Debug\TraceableActionProcessor'
    ) {
        $definition = $container->findDefinition($serviceId);
        $isPublic = $definition->isPublic();
        $definition->setPublic(false);
        $container->setDefinition($serviceId . '.debug.parent', $definition);
        $debugDefinition = new Definition(
            $debugServiceClassName,
            [
                new Reference($serviceId . '.debug.parent'),
                new Reference('oro_api.profiler.logger')
            ]
        );
        $debugDefinition->setPublic(false);
        $container->setDefinition($serviceId . '.debug', $debugDefinition);
        $container->setAlias($serviceId, new Alias($serviceId . '.debug', $isPublic));
    }
}
