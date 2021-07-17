<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\NavigationBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);

        /** @var $root ArrayNode */
        $root = $treeBuilder->buildTree();
        $this->assertInstanceOf(ArrayNode::class, $root);
        $this->assertEquals('oro_navigation', $root->getName());
    }

    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $configs, array $expected): void
    {
        $processor = new Processor();

        $this->assertEquals(
            array_merge(
                [
                    'settings' => [
                        'resolved' => true,
                        'max_items' => [
                            'value' => 20,
                            'scope' => 'app'
                        ],
                        'title_suffix' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'title_delimiter' => [
                            'value' => '-',
                            'scope' => 'app'
                        ],
                        'breadcrumb_menu' => [
                            'value' => 'application_menu',
                            'scope' => 'app'
                        ],
                    ],
                    'js_routing_filename_prefix' => ''
                ],
                $expected
            ),
            $processor->processConfiguration(new Configuration(), $configs)
        );
    }

    public function processConfigurationDataProvider(): array
    {
        return [
            [
                'configs' => [],
                'expected' => [],
            ],
            [
                'configs' => [
                    'oro_navigation' => [
                        'js_routing_filename_prefix' => '/prefix/'
                    ]
                ],
                'expected' => [
                    'js_routing_filename_prefix' => 'prefix_'
                ],
            ],
            [
                'configs' => [
                    'oro_navigation' => [
                        'js_routing_filename_prefix' => '/prefix_'
                    ]
                ],
                'expected' => [
                    'js_routing_filename_prefix' => 'prefix_'
                ],
            ],
            [
                'configs' => [
                    'oro_navigation' => [
                        'js_routing_filename_prefix' => '/_prefix/_'
                    ]
                ],
                'expected' => [
                    'js_routing_filename_prefix' => 'prefix_'
                ],
            ],
            [
                'configs' => [
                    'oro_navigation' => [
                        'js_routing_filename_prefix' => 'prefix'
                    ]
                ],
                'expected' => [
                    'js_routing_filename_prefix' => 'prefix_'
                ],
            ]
        ];
    }
}
