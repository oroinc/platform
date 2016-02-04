<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegistryCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->findDefinition('doctrine')
            ->setClass('Oro\Bundle\EntityBundle\Manager\Registry')
            ->addMethodCall('setDoctrineHelperLink', [new Reference('oro_entity.doctrine_helper.link')]);
    }
}
