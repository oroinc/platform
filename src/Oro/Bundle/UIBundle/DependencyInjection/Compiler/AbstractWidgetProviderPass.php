<?php

namespace Oro\Bundle\UIBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This class provides an algorithm to load prioritized widget providers for different kind of widgets
 */
abstract class AbstractWidgetProviderPass implements CompilerPassInterface
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
            if ($container->hasDefinition($id)) {
                $container->getDefinition($id)->setPublic(false);
            }
            $priority               = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $providers[$priority][] = new Reference($id);
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
            $serviceDef->addMethodCall('addProvider', [$provider]);
        }
    }
}
