<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AssetBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testConfigTreeWithoutAnyOptions(): void
    {
        $processor = new Processor();

        $result = $processor->processConfiguration(new Configuration(), []);

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
     *
     * @param array $options
     * @param array $expects
     */
    public function testConfigTree(array $options, array $expects): void
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $result = $processor->processConfiguration($configuration, [$options]);

        $this->assertEquals($expects, $result);
    }

    /**
     * @return array
     */
    public function dataProviderConfigTree(): array
    {
        return [
            [
                'options' => [
                    'nodejs_path' => 'nodejs',
                    'npm_path' => 'npm',
                ],
                'expects' => [
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
