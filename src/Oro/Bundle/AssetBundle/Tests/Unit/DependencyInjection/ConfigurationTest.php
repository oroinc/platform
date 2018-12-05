<?php

namespace Oro\Bundle\AssetBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AssetBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider dataProviderConfigTree
     */
    public function testConfigTree($options, $expects)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $result = $processor->processConfiguration($configuration, [$options]);

        $this->assertEquals($expects, $result);
    }

    public function dataProviderConfigTree()
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
