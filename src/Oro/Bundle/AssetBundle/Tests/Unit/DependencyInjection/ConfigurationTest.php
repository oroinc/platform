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
        $this->assertEquals(120, $result['build_timeout']);

        $this->assertArrayHasKey('npm_install_timeout', $result);
        $this->assertEquals(600, $result['npm_install_timeout']);

        $this->assertArrayHasKey('nodejs_path', $result);
        $this->assertInternalType('string', $result['nodejs_path']);

        $this->assertArrayHasKey('npm_path', $result);
        $this->assertInternalType('string', $result['npm_path']);
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
                    'build_timeout' => 120,
                    'npm_install_timeout' => 600,
                ],
            ],
            [
                'options' => [
                    'nodejs_path' => 'node',
                    'npm_path' => '/usr/local/bin/npm',
                    'build_timeout' => 300,
                    'npm_install_timeout' => null,
                ],
                'expects' => [
                    'nodejs_path' => 'node',
                    'npm_path' => '/usr/local/bin/npm',
                    'build_timeout' => 300,
                    'npm_install_timeout' => null,
                ],
            ],
        ];
    }
}
