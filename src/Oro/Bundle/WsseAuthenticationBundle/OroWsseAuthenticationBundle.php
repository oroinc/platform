<?php

namespace Oro\Bundle\WsseAuthenticationBundle;

use Oro\Bundle\WsseAuthenticationBundle\DependencyInjection\CompilerPass\WsseNonceCachePass;
use Oro\Bundle\WsseAuthenticationBundle\DependencyInjection\Security\Factory\WsseSecurityListenerFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroWsseAuthenticationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $wsseSecurityListenerFactory = new WsseSecurityListenerFactory();

        $container->addCompilerPass(new WsseNonceCachePass($wsseSecurityListenerFactory->getKey()));

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory($wsseSecurityListenerFactory);
    }
}
