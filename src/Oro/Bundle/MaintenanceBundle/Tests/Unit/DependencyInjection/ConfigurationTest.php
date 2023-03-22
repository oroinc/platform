<?php

namespace Oro\Bundle\MaintenanceBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\MaintenanceBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $configs, array $expected): void
    {
        self::assertEquals($expected, (new Processor())->processConfiguration(new Configuration(), $configs));
    }

    public function processConfigurationDataProvider(): array
    {
        return [
            'empty' => [
                'configs'  => [[]],
                'expected' => [
                    'authorized' => [
                        'path' => null,
                        'host' => null,
                        'ips' => [],
                        'query' => [],
                        'cookie' => [],
                        'route' => null,
                        'attributes' => [],
                    ],
                    'driver' => [
                        'options' => [],
                    ],
                    'response' => [
                        'code' => 503,
                        'status' => 'Service Temporarily Unavailable',
                        'exception_message' => 'Service Temporarily Unavailable',
                    ],
                ],
            ],
            'filled' => [
                'configs'  => [
                    'oro_maintenance' => [
                        'authorized' => [
                            'path' => '/path|.*\.js',
                            'host' => 'your-domain.com',
                            'ips' => ['127.0.0.1'],
                            'query' => ['foo' => 'bar'],
                            'cookie' => ['bar' => 'baz'],
                            'route' => 'route_name',
                            'attributes' => [
                                'foo' => 'bar',
                            ],
                        ],
                        'driver' => [
                            'options' => [
                                'file_path' => '%kernel.root_dir%/../var/cache/lock',
                            ],
                        ],
                        'response' => [
                            'code' => 200,
                            'status' => 'Service Temporarily Unavailable',
                            'exception_message' => 'Service Temporarily Unavailable',
                        ],
                    ],
                ],
                'expected' => [
                    'authorized' => [
                        'path' => '/path|.*\.js',
                        'host' => 'your-domain.com',
                        'ips' => ['127.0.0.1'],
                        'query' => ['foo' => 'bar'],
                        'cookie' => ['bar' => 'baz'],
                        'route' => 'route_name',
                        'attributes' => [
                            'foo' => 'bar',
                        ],
                    ],
                    'driver' => [
                        'options' => [
                            'file_path' => '%kernel.root_dir%/../var/cache/lock',
                        ],
                    ],
                    'response' => [
                        'code' => 200,
                        'status' => 'Service Temporarily Unavailable',
                        'exception_message' => 'Service Temporarily Unavailable',
                    ],
                ],
            ]
        ];
    }
}
