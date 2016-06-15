<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Configuration;
use Oro\Component\Testing\ClassExtensionTrait;
use OroPro\Component\MessageQueue\Transport\Amqp\AmqpConnection;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConfigurationInterface()
    {
        $this->assertClassImplements(ConfigurationInterface::class, Configuration::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new Configuration();
    }

    public function testThrowIfTransportNotConfigured()
    {
        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The child node "transport" at path "oro_message_queue" must be configured.'
        );

        $configuration = new Configuration();

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[]]);
    }

    public function testThrowIfDefaultTransportNotConfigured()
    {
        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The path "oro_message_queue.transport.default" cannot contain an empty value, but got null.'
        );

        $configuration = new Configuration();

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => null,
            ]
        ]]);
    }

    public function testShouldAllowConfigureAmqpTransport()
    {
        if (false == class_exists(AmqpConnection::class)) {
            $this->markTestSkipped('Amqp lib is not installed');
        }

        $configuration = new Configuration();

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
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
        ]]);

        $this->assertEquals([
            'transport' => [
                'default' => 'amqp',
                'amqp' => [
                    'host' => 'theHost',
                    'port' => 'thePort',
                    'user' => 'theUser',
                    'password' => 'thePassword',
                    'vhost' => 'theVhost'
                ],
                'null' => false,
            ]
        ], $config);
    }

    public function testShouldAllowConfigureNullTransport()
    {
        $configuration = new Configuration();

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => 'null',
                'null' => true,
            ]
        ]]);

        $this->assertEquals([
            'transport' => [
                'default' => 'null',
                'null' => true,
            ]
        ], $config);
    }

    public function testShouldSetDefaultConfigurationForClient()
    {
        $configuration = new Configuration();

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
            'client' => null,
        ]]);

        $this->assertEquals([
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
            'client' => [
                'prefix' => 'oro.message_queue.client',
                'router_processor' => null,
                'router_destination' => 'default',
                'default_destination' => 'default',
                'traceable_producer' => false
            ],
        ], $config);
    }

    public function testShouldThrowExceptionIfRouterDestinationIsEmpty()
    {
        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The path "oro_message_queue.client.router_destination" cannot contain an empty value, but got "".'
        );

        $configuration = new Configuration();

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
            'client' => [
                'router_destination' => '',
            ],
        ]]);
    }

    public function testShouldThrowExceptionIfDefaultDestinationIsEmpty()
    {
        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The path "oro_message_queue.client.default_destination" cannot contain an empty value, but got "".'
        );

        $configuration = new Configuration();

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
            'client' => [
                'default_destination' => '',
            ],
        ]]);
    }
}
