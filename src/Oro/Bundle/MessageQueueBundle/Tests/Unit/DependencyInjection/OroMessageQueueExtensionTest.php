<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Configuration;
use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\Mocks\FooTransportFactory;
use Oro\Component\MessageQueue\Client\DbalDriver;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Client\NullDriver;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Component\MessageQueue\DependencyInjection\DefaultTransportFactory;
use Oro\Component\MessageQueue\DependencyInjection\NullTransportFactory;
use Oro\Component\MessageQueue\Transport\Dbal\DbalConnection;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroMessageQueueExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConfigurationInterface()
    {
        self::assertClassExtends(Extension::class, OroMessageQueueExtension::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new OroMessageQueueExtension();
    }

    public function testThrowIfTransportFactoryNameEmpty()
    {
        $extension = new OroMessageQueueExtension();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Transport factory name cannot be empty');
        $extension->addTransportFactory(new FooTransportFactory(null));
    }

    public function testThrowIfTransportFactoryWithSameNameAlreadyAdded()
    {
        $extension = new OroMessageQueueExtension();

        $extension->addTransportFactory(new FooTransportFactory('foo'));
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Transport factory with such name already added. Name foo');
        $extension->addTransportFactory(new FooTransportFactory('foo'));
    }

    public function testShouldConfigureNullTransport()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new NullTransportFactory());

        $extension->load([[
            'transport' => [
                'null' => true
            ]
        ]], $container);

        self::assertTrue($container->hasDefinition('oro_message_queue.transport.null.connection'));
        $connection = $container->getDefinition('oro_message_queue.transport.null.connection');
        self::assertEquals(NullConnection::class, $connection->getClass());
    }

    public function testShouldUseNullTransportAsDefault()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new NullTransportFactory());
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'transport' => [
                'default' => 'null',
                'null' => true
            ]
        ]], $container);

        self::assertEquals(
            'oro_message_queue.transport.default.connection',
            (string) $container->getAlias('oro_message_queue.transport.connection')
        );
        self::assertEquals(
            'oro_message_queue.transport.null.connection',
            (string) $container->getAlias('oro_message_queue.transport.default.connection')
        );
    }

    public function testShouldConfigureFooTransport()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());

        $extension->load([[
            'transport' => [
                'foo' => ['foo_param' => 'aParam'],
            ]
        ]], $container);

        self::assertTrue($container->hasDefinition('foo.connection'));
        $connection = $container->getDefinition('foo.connection');
        self::assertEquals(\stdClass::class, $connection->getClass());
        self::assertEquals([['foo_param' => 'aParam']], $connection->getArguments());
    }

    public function testShouldUseFooTransportAsDefault()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'transport' => [
                'default' => 'foo',
                'foo' => ['foo_param' => 'aParam'],
            ]
        ]], $container);

        self::assertEquals(
            'oro_message_queue.transport.default.connection',
            (string) $container->getAlias('oro_message_queue.transport.connection')
        );
        self::assertEquals(
            'oro_message_queue.transport.foo.connection',
            (string) $container->getAlias('oro_message_queue.transport.default.connection')
        );
    }

    public function testShouldLoadClientServicesWhenEnabled()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'client' => null,
            'transport' => [
                'default' => 'foo',
            ]
        ]], $container);

        self::assertTrue($container->hasDefinition('oro_message_queue.client.config'));
        self::assertTrue($container->hasDefinition('oro_message_queue.client.message_producer'));
    }

    public function testShouldUseMessageProducerByDefault()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'client' => null,
            'transport' => [
                'default' => 'foo',
            ]
        ]], $container);

        $messageProducer = $container->getDefinition('oro_message_queue.client.message_producer');
        self::assertEquals(MessageProducer::class, $messageProducer->getClass());
    }

    public function testShouldUseMessageProducerIfTraceableProducerOptionSetToFalseExplicitly()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'client' => [
                'traceable_producer' => false
            ],
            'transport' => [
                'default' => 'foo',
            ]
        ]], $container);

        $messageProducer = $container->getDefinition('oro_message_queue.client.message_producer');
        self::assertEquals(MessageProducer::class, $messageProducer->getClass());
    }

    public function testShouldUseTraceableMessageProducerIfTraceableProducerOptionSetToTrueExplicitly()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'client' => [
                'traceable_producer' => true
            ],
            'transport' => [
                'default' => 'foo',
            ]
        ]], $container);

        $messageProducer = $container->getDefinition('oro_message_queue.client.traceable_message_producer');
        self::assertEquals(TraceableMessageProducer::class, $messageProducer->getClass());
        self::assertEquals(
            ['oro_message_queue.client.message_producer', null, 0],
            $messageProducer->getDecoratedService()
        );

        self::assertInstanceOf(Reference::class, $messageProducer->getArgument(0));
        self::assertEquals(
            'oro_message_queue.client.traceable_message_producer.inner',
            (string) $messageProducer->getArgument(0)
        );
    }

    public function testShouldConfigureDelayRedeliveredMessageExtension()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'client' => [
                'redelivered_delay_time' => 12345,
            ],
            'transport' => [
                'default' => 'foo',
            ]
        ]], $container);

        $extension = $container->getDefinition('oro_message_queue.client.delay_redelivered_message_extension');
        self::assertEquals(12345, $extension->getArgument(1));
    }

    public function testShouldAddNullConnectionToNullDriverMapToDriverFactory()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'client' => true,
            'transport' => [
                'default' => 'foo',
            ]
        ]], $container);

        self::assertTrue($container->hasDefinition('oro_message_queue.client.driver_factory'));
        $factory = $container->getDefinition('oro_message_queue.client.driver_factory');

        $firstArgument = $factory->getArgument(0);
        self::assertArrayHasKey(NullConnection::class, $firstArgument);
        self::assertEquals(NullDriver::class, $firstArgument[NullConnection::class]);
    }

    public function testShouldAddDbalConnectionToDbalDriverMapToDriverFactory()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'client' => true,
            'transport' => [
                'default' => 'foo',
            ]
        ]], $container);

        self::assertTrue($container->hasDefinition('oro_message_queue.client.driver_factory'));
        $factory = $container->getDefinition('oro_message_queue.client.driver_factory');

        $firstArgument = $factory->getArgument(0);
        self::assertArrayHasKey(DbalConnection::class, $firstArgument);
        self::assertEquals(DbalDriver::class, $firstArgument[DbalConnection::class]);
    }

    public function testShouldAddDbalLazyConnectionToDbalDriverMapToDriverFactory()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new OroMessageQueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'client' => true,
            'transport' => [
                'default' => 'foo',
            ]
        ]], $container);

        self::assertTrue($container->hasDefinition('oro_message_queue.client.driver_factory'));
        $factory = $container->getDefinition('oro_message_queue.client.driver_factory');

        $firstArgument = $factory->getArgument(0);
        self::assertArrayHasKey(DbalConnection::class, $firstArgument);
        self::assertEquals(DbalDriver::class, $firstArgument[DbalConnection::class]);
    }

    public function testShouldAllowGetConfiguration()
    {
        $extension = new OroMessageQueueExtension();

        $configuration = $extension->getConfiguration([], new ContainerBuilder());

        self::assertInstanceOf(Configuration::class, $configuration);
    }
}
