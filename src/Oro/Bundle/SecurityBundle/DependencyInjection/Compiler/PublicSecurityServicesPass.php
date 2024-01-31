<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Makes security services public
 */
class PublicSecurityServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition('security.token_storage')->setPublic(true);
        $container->getDefinition('security.authorization_checker')->setPublic(true);
    }
}
