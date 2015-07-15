<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AclVoterPass implements CompilerPassInterface
{
    const ACL_VOTER = 'security.acl.voter.basic_permissions';
    const SECURITY_CONFIG_PROVIDER = 'oro_entity_config.provider.security';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::ACL_VOTER)) {
            $voterDef = $container->getDefinition(self::ACL_VOTER);

            if ($container->hasDefinition(self::SECURITY_CONFIG_PROVIDER)) {
                $voterDef->addMethodCall('setSecurityConfigProvider', [new Reference(self::SECURITY_CONFIG_PROVIDER)]);
            }
        }
    }
}
