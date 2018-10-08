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
                    'resources' => [Configuration::DEFAULT_LAYOUT_PHP_RESOURCE]
                ],
                'twig' => [
                    'enabled' => true,
                    'resources' => [Configuration::DEFAULT_LAYOUT_TWIG_RESOURCE]
                ]
            ],
            'themes' => [
                'oro-black' => [
                    'label' => 'Oro Black theme',
                    'config' => [
                        'page_templates' => [
                            'templates' => [
                                [
                                    'label' => 'Some label',
                                    'key' => 'some_key',
                                    'route_name' => 'some_route_name',
                                    'description' => null,
                                    'screenshot' => null,
                                    'enabled' => null
                                ],
                                [
                                    'label' => 'Some label (disabled)',
                                    'key' => 'some_key_disabled',
                                    'route_name' => 'some_route_name_disabled',
                                    'description' => null,
                                    'screenshot' => null,
                                    'enabled' => false
                                ]
                            ],
                            'titles' => ['route_1' => 'Title for route 1', 'route_2' => 'Title for route 2'],
                        ],
                        'assets' => [],
                        'extra_config' => ['label' => 'Sample label'],
                    ],
                    'groups' => []
                ]
            ],
            'debug' => '%kernel.debug%'
        ];
        $configs = [
            'oro_layout' => [
                'themes' => [
                    'oro-black' => [
                        'label' => 'Oro Black theme',
                        'config' => [
                            'page_templates' => [
                                'templates' => [
                                    [
                                        'label' => 'Some label',
                                        'key' => 'some_key',
                                        'route_name' => 'some_route_name',
                                    ],
                                    [
                                        'label' => 'Some label (disabled)',
                                        'key' => 'some_key_disabled',
                                        'route_name' => 'some_route_name_disabled',
                                        'enabled' => false
                                    ],
                                ],
                                'titles' => ['route_1' => 'Title for route 1', 'route_2' => 'Title for route 2'],
                            ],
                            'extra_config' => ['label' => 'Sample label'],
                        ]
                    ]
                ]
            ]
        ];
        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }
}
