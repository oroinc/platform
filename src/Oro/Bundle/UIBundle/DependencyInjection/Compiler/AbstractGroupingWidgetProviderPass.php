<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This class provides an algorithm to load prioritized and grouped widget providers for different kind of widgets
 */
abstract class AbstractGroupingWidgetProviderPass implements CompilerPassInterface
{
    /**
     * Gets the id of chain widget provider service
     *
     * @return string
     */
    abstract protected function getChainProviderServiceId();

    /**
     * Gets the tag name of the widget provider
     *
     * @return string
     */
    abstract protected function getProviderTagName();

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function process(ContainerBuilder $container)
    {
        $chainProviderServiceId = $this->getChainProviderServiceId();
        if (!$container->hasDefinition($chainProviderServiceId)) {
            return;
        }

        // find providers
        $providers      = [];
        $taggedServices = $container->findTaggedServiceIds($this->getProviderTagName());
        foreach ($taggedServices as $id => $attributes) {
            $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $group                  = isset($attributes[0]['group']) ? $attributes[0]['group'] : null;
            $providers[$priority][] = [new Reference($id), $group];
        }
        if (empty($providers)) {
            return;
        }

        // sort by priority and flatten
        ksort($providers);
        $providers = call_user_func_array('array_merge', $providers);

        // register
        $serviceDef = $container->getDefinition($chainProviderServiceId);
        foreach ($providers as $provider) {
            $serviceDef->addMethodCall('addProvider', $provider);
        }
    }
}
