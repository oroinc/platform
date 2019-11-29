<?php

namespace Oro\Bundle\PlatformBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Core\Authorization\TraceableAccessDecisionManager;

/**
 * Removing vote listener in order to reduce triggered events quantity
 */
class DebugSecurityVoterCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('debug.security.access.decision_manager')
            && $container->hasDefinition('debug.security.voter.vote_listener')
        ) {
            $definition = $container->getDefinition('debug.security.access.decision_manager');
            if (is_a($definition->getClass(), TraceableAccessDecisionManager::class, true)) {
                $container->removeDefinition('debug.security.voter.vote_listener');
            }
        }
    }
}
