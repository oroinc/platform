<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add Duplicator rules (filter and matcher) for process Extended Entity storage
 */
class ExtendDuplicatorPass implements CompilerPassInterface
{
    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_action.factory.duplicator_factory')) {
            return;
        }

        $container->getDefinition('oro_action.factory.duplicator_factory')
            ->addMethodCall('addRule', [
                ['extend_storage'],
                ['propertyName', ['extendEntityStorage']]
            ])
            // Runs after the configured rules: an object left in the storage by every rule stays by reference
            ->addMethodCall('addRule', [
                ['keep'],
                ['propertyBagObject', []]
            ]);
    }
}
