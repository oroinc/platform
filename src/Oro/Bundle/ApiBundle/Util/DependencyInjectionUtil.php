<?php

namespace Oro\Bundle\ApiBundle\Util;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Oro\Bundle\ApiBundle\DependencyInjection\Configuration;

class DependencyInjectionUtil
{
    /**
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
                $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
                $services[$priority][] = new Reference($id);
            }
            if (empty($services)) {
                return;
            }

            // sort by priority and flatten
            krsort($services);
            $services = call_user_func_array('array_merge', $services);

            // register
            foreach ($services as $service) {
                $chainServiceDef->addMethodCall($addMethodName, [$service]);
            }
        }
    }

    /**
     * Replaces a regular service with the debug one
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
        $container->setAlias($serviceId, $serviceId . '.debug');
    }
}
