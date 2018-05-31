<?php

namespace Oro\Bundle\TestFrameworkBundle\DependencyInjection\Compiler;

use Oro\Bundle\TestFrameworkBundle\EventListener\TestSessionListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestSessionListenerCompilerPass implements CompilerPassInterface
{
    const TEST_SESSION_LISTENER_SERVICE = 'test.session.listener';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::TEST_SESSION_LISTENER_SERVICE)) {
            $definition = $container->getDefinition(self::TEST_SESSION_LISTENER_SERVICE);
            $definition->setClass(TestSessionListener::class);
        }
    }
}
