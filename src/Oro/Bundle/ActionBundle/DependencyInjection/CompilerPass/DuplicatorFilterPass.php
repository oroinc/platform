<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DuplicatorFilterPass implements CompilerPassInterface
{
    const TAG_NAME = 'oro_action.duplicate.filter_type';
    const FACTORY_SERVICE_ID = 'oro_action.factory.duplicator_filter_factory';

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::FACTORY_SERVICE_ID)) {
            return;
        }
        $filters = $container->findTaggedServiceIds(self::TAG_NAME);

        $service = $container->getDefinition(self::FACTORY_SERVICE_ID);

        foreach ($filters as $filterId => $tags) {
            $service->addMethodCall('addObjectType', [new Reference($filterId)]);
        }
    }
}
