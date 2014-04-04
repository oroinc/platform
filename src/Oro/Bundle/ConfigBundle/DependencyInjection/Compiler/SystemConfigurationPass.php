<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\Provider\Provider;

class SystemConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $config    = array();
        $processor = new ProcessorDecorator(new Processor());

        $resources = CumulativeResourceManager::getInstance()
            ->getLoader('OroConfigBundle')
            ->load($container);
        foreach ($resources as $resource) {
            $config = $processor->merge($config, $resource->data);
        }

        $taggedServices = $container->findTaggedServiceIds(Provider::TAG_NAME);
        if ($taggedServices) {
            $config = $processor->process($config);

            foreach ($taggedServices as $id => $attributes) {
                $container
                    ->getDefinition($id)
                    ->replaceArgument(0, $config);
            }
        }
    }
}
