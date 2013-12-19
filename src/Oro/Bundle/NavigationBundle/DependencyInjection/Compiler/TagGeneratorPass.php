<?php

namespace Oro\Bundle\NavigationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TagGeneratorPass implements CompilerPassInterface
{
    const CONTEXT_SERVICE_ID = 'oro_navigation.content.tag_generator_context';
    const TAG_NAME           = 'oro_navigation.tag_generator';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        /**
         * Find and add available generator to context
         */
        $context = $container->getDefinition(self::CONTEXT_SERVICE_ID);
        if ($context) {
            $generators = $container->findTaggedServiceIds(self::TAG_NAME);

            $generators = array_map(
                function ($serviceId) {
                    return new Reference($serviceId);
                },
                array_keys($generators)
            );
            $context->replaceArgument(0, $generators);
        }
    }
}
