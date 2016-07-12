<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use Oro\Component\MessageQueue\Client\MessageProducer;
use Oro\Component\MessageQueue\Client\TraceableMessageProducer;
use Oro\Component\AmqpMessageQueue\Transport\Amqp\AmqpConnection;
use Oro\Component\MessageQueue\Transport\Null\NullConnection;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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

    public function testShouldConfigureAmqpTransport()
    {
        if (false == class_exists(AmqpConnection::class)) {
            $this->markTestSkipped('Amqp lib is not installed');
        }
        
        $container = new ContainerBuilder();

        $extension = new OroMessageQueueExtension();

        $extension->load([[
            'transport' => [
                'default' => 'amqp',
                'amqp' => [
                    'host' => 'theHost',
                    'port' => 'thePort',
                    'user' => 'theUser',
                    'password' => 'thePassword',
                    'vhost' => 'theVhost'
                ]
            ]
        ]], $container);

        $this->assertTrue($container->hasDefinition('oro_message_queue.transport.amqp.connection'));
        $connection = $container->getDefinition('oro_message_queue.transport.amqp.connection');
        $this->assertEquals(AmqpConnection::class, $connection->getClass());
        $this->assertEquals([AmqpConnection::class, 'createFromConfig'], $connection->getFactory());
        $this->assertEquals([
            'host' => 'theHost',
            'port' => 'thePort',
            'user' => 'theUser',
            'password' => 'thePassword',
            'vhost' => 'theVhost'
        ], $connection->getArgument(0));
    }

    public function testShouldUseAmqpTransportAsDefault()
    {
        if (false == class_exists(AmqpConnection::class)) {
            $this->markTestSkipped('Amqp lib is not installed');
        }

        $container = new ContainerBuilder();

        $extension = new OroMessageQueueExtension();

        $extension->load([[
            'transport' => [
                'default' => 'amqp',
                'amqp' => [
                    'host' => 'theHost',
                    'port' => 'thePort',
                    'user' => 'theUser',
                    'password' => 'thePassword',
                    'vhost' => 'theVhost'
                ]
            ]
        ]], $container);

        $this->assertEquals(
            new Alias('oro_message_queue.transport.amqp.connection'),
            $container->getAlias('oro_message_queue.transport.connection')
        );
    }

    public function testShouldConfigureNullTransport()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessageQueueExtension();

        $extension->load([[
            'transport' => [
                'default' => 'null',
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

        $extension->load([[
            'transport' => [
                'default' => 'null',
                'null' => true
            ]
        ]], $container);

        $this->assertEquals(
            new Alias('oro_message_queue.transport.null.connection'),
            $container->getAlias('oro_message_queue.transport.connection')
        );
    }

    public function testShouldLoadClientServicesWhenEnabled()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessageQueueExtension();

        $extension->load([[
            'client' => null,
            'transport' => [
                'default' => 'null',
                'null' => true
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

        $extension->load([[
            'client' => null,
            'transport' => [
                'default' => 'null',
                'null' => true
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

        $extension->load([[
            'client' => [
                'traceable_producer' => false
            ],
            'transport' => [
                'default' => 'null',
                'null' => true
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

        $extension->load([[
            'client' => [
                'traceable_producer' => true
            ],
            'transport' => [
                'default' => 'null',
                'null' => true
            ]
        ]], $container);

        $messageProducer = $container->getDefinition('oro_message_queue.client.message_producer');
        $this->assertEquals(TraceableMessageProducer::class, $messageProducer->getClass());
    }
}
