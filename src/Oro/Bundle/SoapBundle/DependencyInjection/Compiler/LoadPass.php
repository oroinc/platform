<?php

namespace Oro\Bundle\SoapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

class LoadPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $classes = [];

        $configLoader = new CumulativeConfigLoader(
            'oro_soap',
            new YamlCumulativeFileLoader('Resources/config/oro/soap.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            $classes = array_merge($classes, $resource->data['classes']);
        }

        $container
            ->getDefinition('oro_soap.loader')
            ->addArgument(array_unique($classes));
    }
}
