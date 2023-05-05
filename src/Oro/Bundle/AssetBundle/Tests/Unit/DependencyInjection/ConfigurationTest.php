<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AssetBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
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

        $this->assertArrayHasKey('npm_install_timeout', $result);
        $this->assertNull($result['npm_install_timeout']);

        $this->assertArrayHasKey('nodejs_path', $result);
        $this->assertIsString($result['nodejs_path']);

        $this->assertArrayHasKey('npm_path', $result);
        $this->assertIsString($result['npm_path']);
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
                    'npm_path' => 'npm',
                ],
                'expects' => [
                    'with_babel' => false,
                    'nodejs_path' => 'nodejs',
                    'npm_path' => 'npm',
                    'build_timeout' => null,
                    'npm_install_timeout' => null,
                    'webpack_dev_server' => [
                        'enable_hmr' => '%kernel.debug%',
                        'host' => 'localhost',
                        'port' => 8081,
                        'https' => false,
                    ],
                ],
            ],
            [
                'options' => [
                    'with_babel' => false,
                    'nodejs_path' => 'node',
                    'npm_path' => '/usr/local/bin/npm',
                    'build_timeout' => 300,
                    'npm_install_timeout' => null,
                    'webpack_dev_server' => [
                        'enable_hmr' => true,
                        'host' => 'http://example.local',
                        'port' => 65000,
                        'https' => true,
                    ],
                ],
                'expects' => [
                    'with_babel' => false,
                    'nodejs_path' => 'node',
                    'npm_path' => '/usr/local/bin/npm',
                    'build_timeout' => 300,
                    'npm_install_timeout' => null,
                    'webpack_dev_server' => [
                        'enable_hmr' => true,
                        'host' => 'http://example.local',
                        'port' => 65000,
                        'https' => true,
                    ],
                ],
            ],
        ];
    }
}
