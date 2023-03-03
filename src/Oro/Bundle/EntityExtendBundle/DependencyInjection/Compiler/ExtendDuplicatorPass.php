<?php
declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Add Duplicator rule (filter and matcher) for process Extended Entity storage
 */
class ExtendDuplicatorPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('oro_action.factory.duplicator_factory')
            ->addMethodCall('addRule', [
                ['extend_storage'],
                ['propertyName', ['extendEntityStorage']]
            ]);
    }
}
