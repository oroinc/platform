<?php

namespace Oro\Bundle\TestFrameworkBundle;

use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\CheckReferenceCompilerPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\ClientCompilerPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\MakeMailerMessageLoggerListenerPersistentPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\MakeMessageQueueCollectorPersistentPass;
use Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler\TagsInformationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Provides core testing infrastructure and utilities for the Oro application.
 *
 * This bundle serves as the foundation for testing functionality across the platform,
 * offering test fixtures, Behat integration, test entities, and various testing utilities.
 * It includes support for functional testing, Behat acceptance testing, and provides
 * test-specific services and configurations.
 */
class OroTestFrameworkBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TagsInformationPass());
        $container->addCompilerPass(new CheckReferenceCompilerPass());
        $container->addCompilerPass(new ClientCompilerPass());
        $container->addCompilerPass(new MakeMessageQueueCollectorPersistentPass());
        $container->addCompilerPass(new MakeMailerMessageLoggerListenerPersistentPass());
    }
}
