<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DatabaseCheckerCompilerPass implements CompilerPassInterface
{
    const STATE_MANAGER_SERVICE     = 'oro_entity.database_checker.state_manager';
    const DATABASE_CHECKER_TAG_NAME = 'oro_entity.database_checker';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::STATE_MANAGER_SERVICE)) {
            return;
        }

        // find database checkers
        $databaseCheckers = [];
        $taggedServices = $container->findTaggedServiceIds(self::DATABASE_CHECKER_TAG_NAME);
        foreach ($taggedServices as $id => $attributes) {
            $databaseCheckers[] = new Reference($id);
        }

        // register them in the state manager
        $container->getDefinition(self::STATE_MANAGER_SERVICE)
            ->replaceArgument(0, $databaseCheckers);
    }
}
