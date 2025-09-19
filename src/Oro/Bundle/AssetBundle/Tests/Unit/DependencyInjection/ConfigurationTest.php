<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AssetBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private function processConfiguration(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $config);
    }

    public function testConfigTreeWithoutAnyOptions(): void
    {
        $result = $this->processConfiguration([]);

        $this->assertArrayHasKey('with_babel', $result);
        $this->assertFalse($result['with_babel']);

        $this->assertArrayHasKey('build_timeout', $result);
        $this->assertNull($result['build_timeout']);

        $this->assertArrayHasKey('pnpm_install_timeout', $result);
        $this->assertNull($result['pnpm_install_timeout']);

        $this->assertArrayHasKey('nodejs_path', $result);
        $this->assertIsString($result['nodejs_path']);

        $this->assertArrayHasKey('pnpm_path', $result);
        $this->assertIsString($result['pnpm_path']);
    }

    /**
     * @dataProvider dataProviderConfigTree
     */
    public function testConfigTree(array $config, array $expected): void
    {
        $this->assertEquals($expected, $this->processConfiguration([$config]));
    }

    public function dataProviderConfigTree(): array
    {
        return [
            [
                'options' => [
                    'nodejs_path' => 'nodejs',
                    'pnpm_path' => 'pnpm',
                ],
                'expects' => [
                    'with_babel' => false,
                    'nodejs_path' => 'nodejs',
                    'pnpm_path' => 'pnpm',
                    'build_timeout' => null,
                    'pnpm_install_timeout' => null,
                    'webpack_dev_server' => [
                        'enable_hmr' => '%kernel.debug%',
                        'host' => 'localhost',
                        'port' => 8081,
                        'https' => false,
                    ],
                    'external_resources' => [],
                    'settings' => [
                        'resolved' => true,
                        'subresource_integrity_enabled' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                    ]
                ],
            ],
            [
                'options' => [
                    'with_babel' => false,
                    'nodejs_path' => 'node',
                    'pnpm_path' => '/usr/local/bin/pnpm',
                    'build_timeout' => 300,
                    'pnpm_install_timeout' => null,
                    'webpack_dev_server' => [
                        'enable_hmr' => true,
                        'host' => 'http://example.local',
                        'port' => 65000,
                        'https' => true,
                    ],
                    'external_resources' => [
                        'test1' => [
                            'link' => 'http://example.local/test1.js',
                        ]
                    ],
                    'settings' => [
                        'resolved' => true,
                        'subresource_integrity_enabled' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                    ]
                ],
                'expects' => [
                    'with_babel' => false,
                    'nodejs_path' => 'node',
                    'pnpm_path' => '/usr/local/bin/pnpm',
                    'build_timeout' => 300,
                    'pnpm_install_timeout' => null,
                    'webpack_dev_server' => [
                        'enable_hmr' => true,
                        'host' => 'http://example.local',
                        'port' => 65000,
                        'https' => true,
                    ],
                    'external_resources' => [
                        'test1' => [
                            'link' => 'http://example.local/test1.js',
                        ]
                    ],
                    'settings' => [
                        'resolved' => true,
                        'subresource_integrity_enabled' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                    ]
                ],
            ],
        ];
    }
}
