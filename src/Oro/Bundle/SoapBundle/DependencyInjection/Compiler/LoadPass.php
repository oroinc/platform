<?php

namespace Oro\Bundle\SoapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\CacheBundle\Config\Loader\CumulativeConfigLoader;

class LoadPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $classes = [];

        $configLoader = new CumulativeConfigLoader($container);
        $resources    = $configLoader->load('OroSoapBundle');
        foreach ($resources as $resource) {
            $classes = array_merge($classes, $resource->data['classes']);
        }

        $container
            ->getDefinition('oro_soap.loader')
            ->addArgument(array_unique($classes));
    }
}
