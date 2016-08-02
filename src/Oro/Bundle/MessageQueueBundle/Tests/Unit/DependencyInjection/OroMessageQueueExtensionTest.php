<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\Mocks\FooTransportFactory;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Component\MessageQueue\DependencyInjection\DefaultTransportFactory;
use Oro\Component\MessageQueue\DependencyInjection\NullTransportFactory;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Tests\Fragment\Foo;

class OroMessageQueueExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConfigurationInterface()
    {
        $this->assertClassExtends(Extension::class, OroMessageQueueExtension::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new OroMessageQueueExtension();
    }

    public function testThrowIfTransportFactoryNameEmpty()
    {
        $extension = new OroMessageQueueExtension();

        $this->setExpectedException(\LogicException::class, 'Transport factory name cannot be empty');
        $extension->addTransportFactory(new FooTransportFactory(null));
    }

    public function testThrowIfTransportFactoryWithSameNameAlreadyAdded()
    {
        $extension = new OroMessageQueueExtension();

        $extension->addTransportFactory(new FooTransportFactory('foo'));

        $this->setExpectedException(\LogicException::class, 'Transport factory with such name already added. Name foo');
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

        $this->assertTrue($container->hasDefinition('oro_message_queue.transport.null.connection'));
        $connection = $container->getDefinition('oro_message_queue.transport.null.connection');
        $this->assertEquals(NullConnection::class, $connection->getClass());
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

        $this->assertEquals(
            'oro_message_queue.transport.default.connection',
            (string) $container->getAlias('oro_message_queue.transport.connection')
        );
        $this->assertEquals(
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

        $this->assertTrue($container->hasDefinition('foo.connection'));
        $connection = $container->getDefinition('foo.connection');
        $this->assertEquals(\stdClass::class, $connection->getClass());
        $this->assertEquals([['foo_param' => 'aParam']], $connection->getArguments());
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

        $this->assertEquals(
            'oro_message_queue.transport.default.connection',
            (string) $container->getAlias('oro_message_queue.transport.connection')
        );
        $this->assertEquals(
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

        $this->assertTrue($container->hasDefinition('oro_message_queue.client.config'));
        $this->assertTrue($container->hasDefinition('oro_message_queue.client.message_producer'));
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
        $this->assertEquals(MessageProducer::class, $messageProducer->getClass());
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
        $this->assertEquals(MessageProducer::class, $messageProducer->getClass());
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

        $messageProducer = $container->getDefinition('oro_message_queue.client.message_producer');
        $this->assertEquals(TraceableMessageProducer::class, $messageProducer->getClass());
    }
}
