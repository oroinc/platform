<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OwnershipDecisionMakerPass implements CompilerPassInterface
{
    const TAG_NAME = 'oro_security.owner.ownership_decision_maker';
    const CHAIN_SERVICE_ID = 'oro_security.owner.ownership_decision_maker.chain';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::CHAIN_SERVICE_ID)) {
            return;
        }

        $chainServiceDefinition = $container->getDefinition(self::CHAIN_SERVICE_ID);
        $taggedServiceIds = array_keys($container->findTaggedServiceIds(self::TAG_NAME));

        foreach ($taggedServiceIds as $serviceId) {
            $chainServiceDefinition->addMethodCall('addOwnershipDecisionMaker', [new Reference($serviceId)]);
        }
    }
}
