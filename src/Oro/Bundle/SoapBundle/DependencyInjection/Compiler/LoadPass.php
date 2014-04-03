<?php

namespace Oro\Bundle\SoapBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Yaml\Yaml;

class LoadPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $classes = [];

        $bundles = $container->getParameter('kernel.bundles');
        foreach ($bundles as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            $file       = dirname($reflection->getFilename()) . '/Resources/config/oro/soap.yml';
            if (is_file($file)) {
                $classes = array_merge($classes, Yaml::parse(realpath($file))['classes']);
            }
        }

        $container
            ->getDefinition('oro_soap.loader')
            ->addArgument(array_unique($classes));
    }
}
