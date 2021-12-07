<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LoggerBundle\DependencyInjection\Compiler\ErrorLogNotificationMailerHandlerPass;
use Oro\Bundle\LoggerBundle\Monolog\ErrorLogNotificationHandlerWrapper;
use Symfony\Bundle\MonologBundle\DependencyInjection\MonologExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ErrorLogNotificationMailerHandlerPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcessDoesNothingWhenNoHandlers(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->registerExtension(new MonologExtension());
        $containerBuilder->loadFromExtension('monolog');

        $definitions = $containerBuilder->getDefinitions();

        $compilerPass = new ErrorLogNotificationMailerHandlerPass();
        $compilerPass->process($containerBuilder);

        self::assertEquals($definitions, $containerBuilder->getDefinitions());
    }

    public function testProcessDoesNothingWhenNoSymfonyMailerHandler(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->registerExtension(new MonologExtension());
        $containerBuilder->loadFromExtension(
            'monolog',
            [
                'handlers' => [
                    'streamed' => ['type' => 'stream'],
                ],
            ]
        );

        $definitions = $containerBuilder->getDefinitions();

        $compilerPass = new ErrorLogNotificationMailerHandlerPass();
        $compilerPass->process($containerBuilder);

        self::assertEquals($definitions, $containerBuilder->getDefinitions());
    }

    public function testProcessAddsDecoratorWhenSymfonyMailerHandlerPresent(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->registerExtension(new MonologExtension());
        $containerBuilder->loadFromExtension(
            'monolog',
            [
                'handlers' => [
                    'streamed' => ['type' => 'stream'],
                    'mailer' => [
                        'type' => 'symfony_mailer',
                        'from_email' => 'from@example.org',
                        'to_email' => 'to@example.org',
                        'subject' => 'sample subject',
                    ],
                ],
            ]
        );

        $compilerPass = new ErrorLogNotificationMailerHandlerPass();
        $compilerPass->process($containerBuilder);

        self::assertTrue($containerBuilder->has('monolog.handler.mailer.error_log_notification_handler_wrapper'));

        $definition = $containerBuilder->getDefinition('monolog.handler.mailer.error_log_notification_handler_wrapper');
        self::assertEquals(ErrorLogNotificationHandlerWrapper::class, $definition->getClass());
        self::assertEquals(
            [
                new Reference('.inner'),
                new Reference('oro_logger.provider.error_log_notification_recipients'),
            ],
            $definition->getArguments()
        );
        self::assertEquals(
            [
                ['setLogger', [new Reference('logger')]],
            ],
            $definition->getMethodCalls()
        );
        self::assertEquals(['monolog.handler.mailer', null, 32], $definition->getDecoratedService());
    }
}
