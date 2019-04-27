<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\HelpBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration($options, $expects)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $result = $processor->processConfiguration($configuration, [$options]);

        $this->assertEquals($expects, $result);
    }

    public function processConfigurationDataProvider()
    {
        return [
            'minimal_config'  => [
                [
                    'defaults' => ['server' => 'http://server']
                ],
                [
                    'defaults' => ['server' => 'http://server']
                ]
            ],
            'extended_config' => [
                [
                    'defaults' => [
                        'server' => 'http://server',
                        'prefix' => 'prefix',
                        'uri'    => 'uri',
                        'link'   => 'http://server/link'
                    ]
                ],
                [
                    'defaults' => [
                        'server' => 'http://server',
                        'prefix' => 'prefix',
                        'uri'    => 'uri',
                        'link'   => 'http://server/link'
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider processConfigurationFailsDataProvider
     */
    public function testProcessConfigurationFails($options, $expectedException, $expectedExceptionMessage)
    {
        $processor = new Processor();
        $configuration = new Configuration();

        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $processor->processConfiguration($configuration, [$options]);
    }

    public function processConfigurationFailsDataProvider()
    {
        return [
            'no_defaults'    => [
                [],
                InvalidConfigurationException::class,
                'The child node "defaults" at path "oro_help" must be configured.'
            ],
            'no_server'      => [
                [
                    'defaults' => []
                ],
                InvalidConfigurationException::class,
                'The child node "server" at path "oro_help.defaults" must be configured.'
            ],
            'invalid_server' => [
                [
                    'defaults' => [
                        'server' => 'server'
                    ]
                ],
                InvalidConfigurationException::class,
                'Invalid configuration for path "oro_help.defaults.server": Invalid URL "server".'
            ],
            'invalid_link'   => [
                [
                    'defaults' => [
                        'server' => 'http://server',
                        'link'   => 'link'
                    ]
                ],
                InvalidConfigurationException::class,
                'Invalid configuration for path "oro_help.defaults.link": Invalid URL "link".'
            ]
        ];
    }
}
