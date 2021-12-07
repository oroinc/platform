<?php

namespace Oro\Bundle\LoggerBundle\DependencyInjection\Compiler;

use Oro\Bundle\LoggerBundle\Monolog\ErrorLogNotificationHandlerWrapper;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds {@see ErrorLogNotificationHandlerWrapper} decorator for symfony_mailer monolog handler type.
 */
class ErrorLogNotificationMailerHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $configuration = $container->getExtension('monolog')->getConfiguration([], $container);
        $config = (new Processor())->processConfiguration($configuration, $container->getExtensionConfig('monolog'));

        foreach ($config['handlers'] as $handlerName => $handlerConfig) {
            if ($handlerConfig['type'] === 'symfony_mailer') {
                $handlerId = 'monolog.handler.' . $handlerName;

                $wrapperServiceId = $handlerId . '.error_log_notification_handler_wrapper';

                $container
                    ->register($wrapperServiceId, ErrorLogNotificationHandlerWrapper::class)
                    ->setArguments(
                        [
                            new Reference('.inner'),
                            new Reference('oro_logger.provider.error_log_notification_recipients'),
                        ]
                    )
                    ->addMethodCall('setLogger', [new Reference('logger')])
                    // Priority is set to 32 to make sure it is applied before decorators
                    // from {@see ConfigurableLoggerPass}.
                    ->setDecoratedService($handlerId, null, 32);
            }
        }
    }
}
