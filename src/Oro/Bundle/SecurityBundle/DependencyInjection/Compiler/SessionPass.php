<?php

namespace Oro\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * - Configures HTTP session to be able to set the cookie path for session cookie if application
 * was installed in subfolder
 * - Adds tag to native file handler
 * - Validates and optimizes oro_session_handler tagged services$
 */
class SessionPass implements CompilerPassInterface
{
    public const HTTP_KERNEL_DECORATOR_SERVICE = 'oro_security.http_kernel.session_path';

    #[\Override]
    public function process(ContainerBuilder $container)
    {
        $this->tagNativeFileSessionHandler($container);
        $this->processSessionHandlers($container);
    }

    public function tagNativeFileSessionHandler(ContainerBuilder $container): void
    {
        $nativeSessionHandler = $container->getDefinition('session.handler.native_file');
        $nativeSessionHandler->addTag('oro_session_handler', ['alias' => 'native']);
    }

    public function processSessionHandlers(ContainerBuilder $container): void
    {
        $sessionHandlers = $container->findTaggedServiceIds('oro_session_handler');
        foreach ($sessionHandlers as $sessionHandlerId => $tags) {
            $definition = $container->getDefinition($sessionHandlerId);
            $class = $definition->getClass();
            if (!is_a($class, \SessionHandlerInterface::class, true)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'The class of the "%s" service must implement "%s"',
                        $sessionHandlerId,
                        \SessionHandlerInterface::class
                    )
                );
            }
        }
    }
}
