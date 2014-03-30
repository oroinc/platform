<?php

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityManagerPass implements CompilerPassInterface
{
    const EXTEND_CONFIG_PROVIDER_SERVICE_KEY = 'oro_entity_config.provider.extend';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $em = $container->findDefinition('doctrine.orm.entity_manager');
        $em->addMethodCall('setExtendConfigProvider', [new Reference(self::EXTEND_CONFIG_PROVIDER_SERVICE_KEY)]);
    }
}
