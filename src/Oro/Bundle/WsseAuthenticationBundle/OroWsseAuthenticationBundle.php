<?php

namespace Oro\Bundle\WsseAuthenticationBundle;

use Oro\Bundle\WsseAuthenticationBundle\DependencyInjection\Security\Factory\WsseSecurityListenerFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The OroWsseAuthenticationBundle bundle class:
 *  - adds security listener factory to enable WSSE authentication
 */
class OroWsseAuthenticationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new WsseSecurityListenerFactory());
    }
}
