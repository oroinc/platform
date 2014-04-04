<?php

namespace Oro\Bundle\SoapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\CacheBundle\Config\CumulativeResourceManager;

class LoadPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $classes = [];

        $resources = CumulativeResourceManager::getInstance()
            ->getLoader('OroSoapBundle')
            ->load($container);
        foreach ($resources as $resource) {
            $classes = array_merge($classes, $resource->data['classes']);
        }

        $container
            ->getDefinition('oro_soap.loader')
            ->addArgument(array_unique($classes));
    }
}
