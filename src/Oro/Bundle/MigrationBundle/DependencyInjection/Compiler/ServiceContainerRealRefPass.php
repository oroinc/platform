<?php

/*
 * @codingStandardsIgnoreStart
 *
 * This file is a copy of {@see Symfony\Bundle\FrameworkBundle\DependencyInjection\Compiler\TestServiceContainerRealRefPass}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * @codingStandardsIgnoreEnd
 */

namespace Oro\Bundle\MigrationBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Rebuilds the service locator services with real service references.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class ServiceContainerRealRefPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('oro_migration.service_container')) {
            return;
        }

        $migrationContainer = $container->getDefinition('oro_migration.service_container');
        $privateContainer = $container->getDefinition((string) $migrationContainer->getArgument(2));
        $definitions = $container->getDefinitions();

        /** @var ServiceClosureArgument $argument */
        foreach ($privateContainer->getArgument(0) as $id => $argument) {
            $target = (string) $argument->getValues()[0];

            if (isset($definitions[$target])) {
                $argument->setValues([new Reference($target)]);
            }
        }
    }
}
