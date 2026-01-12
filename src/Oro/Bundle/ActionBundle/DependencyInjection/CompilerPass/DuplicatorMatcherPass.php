<?php

namespace Oro\Bundle\ActionBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers duplicator matcher services with the duplicator matcher factory.
 *
 * This compiler pass collects all services tagged with `oro_action.duplicate.matcher_type`
 * and registers them with the duplicator matcher factory for entity duplication matching logic.
 */
class DuplicatorMatcherPass implements CompilerPassInterface
{
    public const TAG_NAME = 'oro_action.duplicate.matcher_type';
    public const FACTORY_SERVICE_ID = 'oro_action.factory.duplicator_matcher_factory';

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    #[\Override]
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
