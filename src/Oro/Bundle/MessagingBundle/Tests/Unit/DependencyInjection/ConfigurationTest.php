<?php
namespace Oro\Bundle\MessagingBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MessagingBundle\DependencyInjection\Configuration;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\Config\Definition\ConfigurationInterface;
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

    public function testShouldAcceptEmptyConfiguration()
    {
        $configuration = new Configuration();

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[]]);

        $this->assertEquals([], $config);
    }

    public function testShouldAllowConfigureAmqpTransport()
    {
        $configuration = new Configuration();

        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'transport' => [
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
                'null' => true,
            ]
        ]]);

        $this->assertEquals([
            'transport' => [
                'null' => true,
            ]
        ], $config);
    }
}
