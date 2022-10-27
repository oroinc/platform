<?php

namespace Oro\Bundle\DataAuditBundle\DependencyInjection\CompilerPass;

use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Pass all processors tagged with oro_dataaudit.entity_strategy_processor to registry during compile.
 */
class EntityAuditStrategyPass implements CompilerPassInterface
{
    private const REGISTRY_SERVICE = 'oro_dataaudit.strategy_processor.entity_audit_strategy_registry';
    private const TAG = 'oro_dataaudit.entity_strategy_processor';

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::REGISTRY_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);
        if (empty($taggedServices)) {
            return;
        }

        $registryDefinition = $container->getDefinition(self::REGISTRY_SERVICE);

        foreach ($taggedServices as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tag) {
                if (!isset($tag['entityName']) || !$tag['entityName']) {
                    throw new LogicException(sprintf(
                        'Entity name is not set but it is required. Service: "%s", tag: "%s"',
                        $serviceId,
                        self::TAG
                    ));
                }

                $registryDefinition->addMethodCall(
                    'addProcessor',
                    [new Reference($serviceId), $tag['entityName']]
                );
            }
        }
    }
}
