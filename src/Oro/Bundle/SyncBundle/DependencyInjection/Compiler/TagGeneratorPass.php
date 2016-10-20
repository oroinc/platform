<?php

namespace Oro\Bundle\SyncBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

class TagGeneratorPass implements CompilerPassInterface
{
    const CHAIN_SERVICE_ID = 'oro_sync.content.tag_generator_chain';
    const TAG_NAME         = 'oro_sync.tag_generator';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        /**
         * Find and add available generator to context
         */
        $context = $container->getDefinition(self::CHAIN_SERVICE_ID);
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
