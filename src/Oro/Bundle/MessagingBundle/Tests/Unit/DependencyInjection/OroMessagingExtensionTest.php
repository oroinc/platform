<?php
namespace Oro\Bundle\MessagingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessagingBundle\DependencyInjection\OroMessagingExtension;
use Oro\Component\Messaging\Transport\Amqp\AmqpConnection;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession;
use Oro\Component\Messaging\Transport\Null\NullConnection;
use Oro\Component\Messaging\Transport\Null\NullSession;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
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

    public function testThrowIfTransportNotConfigured()
    {
        $extension = new OroMessagingExtension();

        $this->setExpectedException(\LogicException::class, 'Default transport is not configured.');
        $extension->load([], new ContainerBuilder());
    }

    public function testShouldConfigureAmqpTransport()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessagingExtension();

        $extension->load([[
            'transport' => [
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

        $this->assertTrue($container->hasDefinition('oro_messaging.transport.amqp.session'));
        $session = $container->getDefinition('oro_messaging.transport.amqp.session');
        $this->assertEquals(AmqpSession::class, $session->getClass());
        $this->assertEquals(
            [new Reference('oro_messaging.transport.amqp.connection'), 'createSession'],
            $session->getFactory()
        );
    }

    public function testShouldUseAmqpTransportAsDefault()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessagingExtension();

        $extension->load([[
            'transport' => [
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
            new Alias('oro_messaging.transport.amqp.session'),
            $container->getAlias('oro_messaging.transport.session')
        );
    }

    public function testShouldConfigureNullTransport()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessagingExtension();

        $extension->load([[
            'transport' => [
                'null' => true
            ]
        ]], $container);

        $this->assertTrue($container->hasDefinition('oro_messaging.transport.null.connection'));
        $connection = $container->getDefinition('oro_messaging.transport.null.connection');
        $this->assertEquals(NullConnection::class, $connection->getClass());

        $this->assertTrue($container->hasDefinition('oro_messaging.transport.null.session'));
        $session = $container->getDefinition('oro_messaging.transport.null.session');
        $this->assertEquals(NullSession::class, $session->getClass());
        $this->assertEquals(
            [new Reference('oro_messaging.transport.null.connection'), 'createSession'],
            $session->getFactory()
        );
    }

    public function testShouldUseNullTransportAsDefault()
    {
        $container = new ContainerBuilder();

        $extension = new OroMessagingExtension();

        $extension->load([[
            'transport' => [
                'null' => true
            ]
        ]], $container);

        $this->assertEquals(
            new Alias('oro_messaging.transport.null.session'),
            $container->getAlias('oro_messaging.transport.session')
        );
    }
}
