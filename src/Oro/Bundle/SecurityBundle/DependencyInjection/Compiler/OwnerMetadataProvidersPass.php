<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class OwnerMetadataProvidersPass implements CompilerPassInterface
{
    const TAG_NAME = 'oro_security.owner.metadata_provider';
    const CHAIN_SERVICE_ID = 'oro_security.owner.metadata_provider.chain';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::CHAIN_SERVICE_ID)) {
            return;
        }

        $chainServiceDefinition = $container->getDefinition(self::CHAIN_SERVICE_ID);
        $taggedServices = $container->findTaggedServiceIds(self::TAG_NAME);

        foreach ($taggedServices as $id => $attributes) {
            if (empty($attributes[0]['alias'])) {
                throw new \InvalidArgumentException(
                    sprintf('Tag %s alias is missing for %s service', self::TAG_NAME, $id)
                );
            }

            $chainServiceDefinition->addMethodCall('addProvider', [$attributes[0]['alias'], new Reference($id)]);
        }
    }
}
