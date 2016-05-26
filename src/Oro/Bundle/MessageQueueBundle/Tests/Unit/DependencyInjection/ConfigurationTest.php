<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Configuration;
use Oro\Component\Testing\ClassExtensionTrait;
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

    public function testShouldSetDefaultConfigurationForZeroConfig()
    {
        $configuration = new Configuration();

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
            'zero_config' => null,
        ]]);

        $this->assertEquals([
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
            'zero_config' => [
                'prefix' => 'oro.message_queue.zero_config',
                'router_processor' => null,
                'router_destination' => 'default',
                'default_destination' => 'default',
            ],
        ], $config);
    }

    public function testShouldThrowExceptionIfRouterDestinationIsEmpty()
    {
        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The path "oro_message_queue.zero_config.router_destination" cannot contain an empty value, but got "".'
        );

        $configuration = new Configuration();

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
            'zero_config' => [
                'router_destination' => '',
            ],
        ]]);
    }

    public function testShouldThrowExceptionIfDefaultDestinationIsEmpty()
    {
        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The path "oro_message_queue.zero_config.default_destination" cannot contain an empty value, but got "".'
        );

        $configuration = new Configuration();

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
            'zero_config' => [
                'default_destination' => '',
            ],
        ]]);
    }
}
