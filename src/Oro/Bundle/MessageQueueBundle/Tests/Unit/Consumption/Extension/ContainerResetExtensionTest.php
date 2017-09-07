<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Container;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ContainerResetExtension;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class ContainerResetExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testOnPreReceivedShouldNotResetPersistentServices()
    {
        $persistentService = new \stdClass();
        $container = new Container();
        $container->set('persistent_service', $persistentService);
        $container->set('not_persistent_service', new \stdClass());

        $message = new NullMessage();
        $message->setProperties([Config::PARAMETER_PROCESSOR_NAME => 'test']);
        $logger = $this->createMock(LoggerInterface::class);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessage($message);
        $context->setLogger($logger);

        $logger->expects($this->once())
            ->method('info')
            ->with('Reset the container');

        // guard
        $this->assertTrue($container->initialized('persistent_service'));
        $this->assertFalse($container->initialized('persistent_uninitialized_service'));
        $this->assertTrue($container->initialized('not_persistent_service'));

        $extension = new ContainerResetExtension($container);
        $extension->setPersistentServices(['persistent_service', 'persistent_uninitialized_service']);
        $extension->onPreReceived($context);

        $this->assertTrue($container->initialized('persistent_service'));
        $this->assertSame($persistentService, $container->get('persistent_service'));
        $this->assertFalse($container->initialized('persistent_uninitialized_service'));
        $this->assertFalse($container->initialized('not_persistent_service'));
    }

    public function testOnPreReceivedShouldNotResetServicesForPersistentProcessor()
    {
        $service = new \stdClass();
        $container = new Container();
        $container->set('service', $service);

        $message = new NullMessage();
        $message->setProperties([Config::PARAMETER_PROCESSOR_NAME => 'test_processor']);
        $logger = $this->createMock(LoggerInterface::class);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setMessage($message);
        $context->setLogger($logger);

        $logger->expects($this->never())
            ->method('info');

        $extension = new ContainerResetExtension($container);
        $extension->setPersistentProcessors(['test_processor']);
        $extension->onPreReceived($context);

        $this->assertTrue($container->initialized('service'));
        $this->assertSame($service, $container->get('service'));
    }
}
