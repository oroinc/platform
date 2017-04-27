<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DuplicatorMatcherPass implements CompilerPassInterface
{
    const TAG_NAME = 'oro_action.duplicate.matcher_type';
    const FACTORY_SERVICE_ID = 'oro_action.factory.duplicator_matcher_factory';

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
        $matchers = $container->findTaggedServiceIds(self::TAG_NAME);

        $service = $container->getDefinition(self::FACTORY_SERVICE_ID);

        foreach ($matchers as $matcher => $tags) {
            $service->addMethodCall('addObjectType', [new Reference($matcher)]);
        }
    }
}
