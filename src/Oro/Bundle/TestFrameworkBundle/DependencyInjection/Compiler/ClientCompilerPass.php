<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Oro\Bundle\TestFrameworkBundle\Test\Client;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Replaces the test client service with the Oro-specific test client implementation.
 *
 * This compiler pass ensures that functional tests use the enhanced {@see Client} class
 * which provides additional testing capabilities specific to the Oro platform.
 */
class ClientCompilerPass implements CompilerPassInterface
{
    const CLIENT_SERVICE = 'test.client';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::CLIENT_SERVICE)) {
            $definition = $container->getDefinition(self::CLIENT_SERVICE);
            $definition->setClass(Client::class);
        }
    }
}
