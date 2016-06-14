<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\OroMessageQueueExtension;
use OroPro\Component\MessageQueue\Transport\Amqp\AmqpConnection;
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
}
