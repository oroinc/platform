<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LayoutBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function testProcessConfiguration()
    {
        $configuration = new Configuration();
        $processor     = new Processor();
        $expected = [
            'settings' => [
                'resolved' => true,
                'development_settings_feature_enabled' => [
                    'value' => '%kernel.debug%',
                    'scope' => 'app'
                ],
                'debug_block_info' => [
                    'value' => false,
                    'scope' => 'app'
                ],
                'debug_developer_toolbar' => [
                    'value' => true,
                    'scope' => 'app'
                ],
            ],
            'view' => ['annotations' => true],
            'templating' => [
                'default' => 'twig',
                'php' => [
                    'enabled' => true,
                    'resources' => ['OroLayoutBundle:Layout/php']
                ],
                'twig' => [
                    'enabled' => true,
                    'resources' => ['OroLayoutBundle:Layout:div_layout.html.twig']
                ]
            ],
            'debug' => '%kernel.debug%',
            "enabled_themes" => []
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
