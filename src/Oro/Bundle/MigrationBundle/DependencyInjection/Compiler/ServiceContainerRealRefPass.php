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
        $privateContainer = $migrationContainer->getArgument(2);
        $definitions = $container->getDefinitions();
        $privateServices = $privateContainer->getArgument(0);

        /** @var ServiceClosureArgument $argument */
        foreach ($privateServices as $id => $argument) {
            if (isset($definitions[$target = (string) $argument->getValues()[0]])) {
                $argument->setValues([new Reference($target)]);
            } else {
                unset($privateServices[$id]);
            }
        }

        $privateContainer->replaceArgument(0, $privateServices);
    }
}
