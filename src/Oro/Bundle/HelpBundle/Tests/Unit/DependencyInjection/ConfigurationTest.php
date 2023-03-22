<?php

namespace Oro\Bundle\HelpBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\HelpBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private function processConfiguration(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $config);
    }

    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $config, array $expected): void
    {
        $this->assertEquals($expected, $this->processConfiguration([$config]));
    }

    public function processConfigurationDataProvider(): array
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
    public function testProcessConfigurationFails(
        array $config,
        string $expectedException,
        string $expectedExceptionMessage
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->processConfiguration([$config]);
    }

    public function processConfigurationFailsDataProvider(): array
    {
        return [
            'no_defaults'    => [
                [],
                InvalidConfigurationException::class,
                'The child config "defaults" under "oro_help" must be configured.'
            ],
            'no_server'      => [
                [
                    'defaults' => []
                ],
                InvalidConfigurationException::class,
                'The child config "server" under "oro_help.defaults" must be configured.'
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
