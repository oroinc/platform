<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Configuration;
use Oro\Bundle\MessageQueueBundle\Tests\Unit\Mocks\FooTransportFactory;
use Oro\Component\MessageQueue\DependencyInjection\DefaultTransportFactory;
use Oro\Component\MessageQueue\DependencyInjection\NullTransportFactory;
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

    public function testCouldBeConstructedWithFactoriesAsFirstArgument()
    {
        new Configuration([]);
    }

    public function testThrowIfTransportNotConfigured()
    {
        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The child node "transport" at path "oro_message_queue" must be configured.'
        );

        $configuration = new Configuration([]);

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[]]);
    }

    public function testShouldInjectFooTransportFactoryConfig()
    {
        $configuration = new Configuration([new FooTransportFactory()]);

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => [
                'foo' => [
                    'foo_param' => 'aParam'
                ],
            ]
        ]]);
    }

    public function testThrowExceptionIfFooTransportConfigInvalid()
    {
        $configuration = new Configuration([new FooTransportFactory()]);

        $processor = new Processor();

        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The path "oro_message_queue.transport.foo.foo_param" cannot contain an empty value, but got null.'
        );

        $processor->processConfiguration($configuration, [[
            'transport' => [
                'foo' => [
                    'foo_param' => null
                ],
            ]
        ]]);
    }

    public function testShouldAllowConfigureDefaultTransport()
    {
        $configuration = new Configuration([new DefaultTransportFactory()]);

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => ['alias' => 'foo'],
            ]
        ]]);
    }

    public function testShouldAllowConfigureNullTransport()
    {
        $configuration = new Configuration([new NullTransportFactory()]);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [
                'null' => true,
            ]
        ]]);

        $this->assertEquals([
            'transport' => [
                'null' => [],
            ]
        ], $config);
    }

    public function testShouldAllowConfigureSeveralTransportsSameTime()
    {
        $configuration = new Configuration([
            new NullTransportFactory(),
            new DefaultTransportFactory(),
            new FooTransportFactory(),
        ]);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => 'foo',
                'null' => true,
                'foo' => ['foo_param' => 'aParam'],
            ]
        ]]);

        $this->assertEquals([
            'transport' => [
                'default' => ['alias' => 'foo'],
                'null' => [],
                'foo' => ['foo_param' => 'aParam'],
            ]
        ], $config);
    }

    public function testShouldSetDefaultConfigurationForClient()
    {
        $configuration = new Configuration([new DefaultTransportFactory()]);

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => ['alias' => 'foo'],
            ],
            'client' => null,
        ]]);

        $this->assertEquals([
            'transport' => [
                'default' => ['alias' => 'foo'],
            ],
            'client' => [
                'prefix' => 'oro',
                'router_processor' => null,
                'router_destination' => 'default',
                'default_destination' => 'default',
                'traceable_producer' => false
            ],
        ], $config);
    }

    public function testThrowExceptionIfRouterDestinationIsEmpty()
    {
        $this->setExpectedException(
            InvalidConfigurationException::class,
            'The path "oro_message_queue.client.router_destination" cannot contain an empty value, but got "".'
        );

        $configuration = new Configuration([new DefaultTransportFactory()]);

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => ['alias' => 'foo'],
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

        $configuration = new Configuration([new DefaultTransportFactory()]);

        $processor = new Processor();
        $processor->processConfiguration($configuration, [[
            'transport' => [
                'default' => ['alias' => 'foo'],
            ],
            'client' => [
                'default_destination' => '',
            ],
        ]]);
    }
}
