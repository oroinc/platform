<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\LayoutBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();
        self::assertInstanceOf(TreeBuilder::class, $treeBuilder);
    }

    public function testProcessConfiguration(): void
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
                'twig' => [
                    'resources' => ['@OroLayout/Layout/div_layout.html.twig']
                ]
            ],
            'debug' => '%kernel.debug%',
            "enabled_themes" => []
        ];

        self::assertEquals($expected, $processor->processConfiguration($configuration, []));
    }
}
