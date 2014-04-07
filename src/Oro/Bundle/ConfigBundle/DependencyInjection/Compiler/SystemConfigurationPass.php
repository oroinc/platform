<?php

namespace Oro\Bundle\ConfigBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ConfigBundle\DependencyInjection\SystemConfiguration\ProcessorDecorator;
use Oro\Bundle\ConfigBundle\Provider\Provider;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class SystemConfigurationPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $config    = array();
        $processor = new ProcessorDecorator(new Processor());

        $configLoader = new CumulativeConfigLoader(
            'oro_system_configuration',
            new YamlCumulativeFileLoader('Resources/config/system_configuration.yml')
        );
        $resources    = $configLoader->load($container);
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
