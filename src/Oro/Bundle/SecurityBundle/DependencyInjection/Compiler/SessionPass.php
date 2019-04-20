<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Oro\Bundle\SecurityBundle\Request\SessionHttpKernelDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Configures HTTP session to be able to set the cookie path for session cookie if application
 * was installed in subfolder.
 */
class SessionPass implements CompilerPassInterface
{
    public const HTTP_KERNEL_DECORATOR_SERVICE = 'oro_security.http_kernel.session_path';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container
            ->register(self::HTTP_KERNEL_DECORATOR_SERVICE, SessionHttpKernelDecorator::class)
            ->setArguments([
                new Reference(self::HTTP_KERNEL_DECORATOR_SERVICE . '.inner'),
                new Reference('service_container')
            ])
            ->setDecoratedService('http_kernel', null, 250)
            ->setPublic(false);
    }
}
