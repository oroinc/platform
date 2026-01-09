<?php

namespace Oro\Bundle\EntityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers database checkers in the dependency injection container.
 *
 * This compiler pass collects all services tagged with `oro_entity.database_checker`
 * and registers them with the database state manager. It enables the system to check
 * the database state using multiple pluggable checkers.
 */
class DatabaseCheckerCompilerPass implements CompilerPassInterface
{
    public const STATE_MANAGER_SERVICE     = 'oro_entity.database_checker.state_manager';
    public const DATABASE_CHECKER_TAG_NAME = 'oro_entity.database_checker';

    #[\Override]
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
