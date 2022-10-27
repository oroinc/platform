<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\Http\Firewall\ExceptionListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Overrides class for security.exception_listener and sets excluded routes from a parameter
 */
class SetFirewallExceptionListenerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container
            ->getDefinition('security.exception_listener')
            ->setClass(ExceptionListener::class)
            ->addMethodCall('setExcludedRoutes', ['%oro_security.login_target_path_excludes%']);
    }
}
