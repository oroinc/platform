<?php

namespace Oro\Bundle\LDAPBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LdapConfigurationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->updateConfiguration(
            $container,
            'fr3d_ldap.ldap_driver.zend.driver',
            'Oro\Bundle\LDAPBundle\LDAP\Ldap'
        );
    }

    /**
     * @param ContainerBuilder $container
     * @param string $service
     * @param string $class
     */
    protected function updateConfiguration(ContainerBuilder $container, $service, $class)
    {
        $serviceDef = $container->findDefinition($service);
        $serviceDef->setClass($class);
        $serviceDef->addMethodCall('updateOroConfiguration', [
            new Reference('oro_config.global'),
        ]);
    }
}
