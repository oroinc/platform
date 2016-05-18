<?php
namespace Oro\Bundle\MessagingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessagingBundle\DependencyInjection\OroMessagingExtension;
use Oro\Component\Messaging\Transport\Amqp\AmqpConnection;
use Oro\Component\Messaging\Transport\Null\NullConnection;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OroMessagingExtensionTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConfigurationInterface()
    {
        $this->assertClassExtends(Extension::class, OroMessagingExtension::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new OroMessagingExtension();
    }

    public function testShouldConfigureAmqpTransport()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessagingExtension();

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

        $this->assertTrue($container->hasDefinition('oro_messaging.transport.amqp.connection'));
        $connection = $container->getDefinition('oro_messaging.transport.amqp.connection');
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

        $extension = new OroMessagingExtension();

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
            new Alias('oro_messaging.transport.amqp.connection'),
            $container->getAlias('oro_messaging.transport.connection')
        );
    }

    public function testShouldConfigureNullTransport()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessagingExtension();

        $extension->load([[
            'transport' => [
                'default' => 'null',
                'null' => true
            ]
        ]], $container);

        $this->assertTrue($container->hasDefinition('oro_messaging.transport.null.connection'));
        $connection = $container->getDefinition('oro_messaging.transport.null.connection');
        $this->assertEquals(NullConnection::class, $connection->getClass());
    }

    public function testShouldUseNullTransportAsDefault()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessagingExtension();

        $extension->load([[
            'transport' => [
                'default' => 'null',
                'null' => true
            ]
        ]], $container);

        $this->assertEquals(
            new Alias('oro_messaging.transport.null.connection'),
            $container->getAlias('oro_messaging.transport.connection')
        );
    }
}
