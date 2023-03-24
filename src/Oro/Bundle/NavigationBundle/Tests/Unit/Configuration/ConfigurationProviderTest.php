<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Configuration;

use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\BarBundle\BarBundle;
use Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\FooBundle\FooBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class ConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private ConfigurationProvider $configurationProvider;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('NavigationConfigurationProvider');

        $this->configurationProvider = new ConfigurationProvider($cacheFile, false);
    }

    public function testEmptyConfig()
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([]);

        $this->assertSame([], $this->configurationProvider->getMenuTree());
        $this->assertSame([], $this->configurationProvider->getMenuItems());
        $this->assertSame([], $this->configurationProvider->getMenuTemplates());
        $this->assertSame([], $this->configurationProvider->getNavigationElements());
        $this->assertNull($this->configurationProvider->getTitle('test'));
    }

    public function testMenuTree()
    {
        $bundle1 = new BarBundle();
        $bundle2 = new FooBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $this->assertEquals(
            [
                'application_menu' => [
                    'type'              => 'application_menu',
                    'scope_type'        => 'default',
                    'max_nesting_level' => 1,
                    'children'          => [
                        'customers_tab' => [
                            'children'       => [
                                'call_list'   => [
                                    'children'       => [],
                                    'merge_strategy' => 'move'
                                ],
                                'to_replace'  => [
                                    'children'       => [
                                        'to_replace_new_child' => [
                                            'children'       => [],
                                            'merge_strategy' => 'move'
                                        ]
                                    ],
                                    'merge_strategy' => 'replace'
                                ],
                                'to_move_top' => [
                                    'children'       => [
                                        'to_move_top_child'     => [
                                            'children'       => [],
                                            'merge_strategy' => 'move'
                                        ],
                                        'to_move_top_new_child' => [
                                            'children'       => [],
                                            'merge_strategy' => 'move'
                                        ]
                                    ],
                                    'merge_strategy' => 'move'
                                ]
                            ],
                            'merge_strategy' => 'move'
                        ],
                        'default_tab'   => [
                            'children'       => [],
                            'merge_strategy' => 'move'
                        ]
                    ],
                    'extras'            => []
                ],
                'shortcuts'        => [
                    'type'       => 'shortcuts',
                    'scope_type' => 'custom',
                    'read_only'  => true,
                    'children'   => [
                        'shortcut_call_list' => [
                            'children'       => [],
                            'merge_strategy' => 'move'
                        ]
                    ],
                    'extras'     => []
                ],
                'quicklinks'       => [
                    'type'     => 'quicklinks',
                    'children' => [
                        'quicklinks_request_quote' => [
                            'children'       => [],
                            'merge_strategy' => 'move'
                        ]
                    ],
                    'extras'   => []
                ]
            ],
            $this->configurationProvider->getMenuTree()
        );
    }

    public function testMenuItems()
    {
        $bundle1 = new BarBundle();
        $bundle2 = new FooBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $defaultParams = [
            'translateParameters' => [],
            'routeParameters'     => [],
            'extras'              => []
        ];

        $this->assertEquals(
            [
                'customers_tab'            => array_merge(
                    ['label' => 'Customers', 'read_only' => true],
                    $defaultParams
                ),
                'call_list'                => array_merge(['label' => 'Calls'], $defaultParams),
                'to_replace'               => array_merge(['label' => 'To replace'], $defaultParams),
                'to_move_top'              => array_merge(['label' => 'To move'], $defaultParams),
                'shortcut_call_list'       => array_merge(['label' => 'Show list'], $defaultParams),
                'quicklinks_request_quote' => array_merge(['label' => 'Request Quote'], $defaultParams),
                'default_tab'              => array_merge(['label' => 'Default'], $defaultParams),
                'to_replace_child'         => array_merge(['label' => 'To replace child'], $defaultParams),
                'to_move_top_child'        => array_merge(['label' => 'To move top child'], $defaultParams),
                'to_replace_new_child'     => array_merge(['label' => 'To replace new child'], $defaultParams),
                'to_move_top_new_child'    => array_merge(['label' => 'To move top new child'], $defaultParams)
            ],
            $this->configurationProvider->getMenuItems()
        );
    }

    public function testMenuTemplates()
    {
        $bundle1 = new BarBundle();
        $bundle2 = new FooBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $this->assertEquals(
            [],
            $this->configurationProvider->getMenuTemplates()
        );
    }

    public function testNavigationElements()
    {
        $bundle1 = new BarBundle();
        $bundle2 = new FooBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $this->assertEquals(
            [
                'favoriteButton' => [
                    'default' => true,
                    'routes'  => [
                        'call_list'  => false,
                        'some_route' => false
                    ]
                ],
                'shortcutsPanel' => [
                    'default' => true,
                    'routes'  => [
                        'call_list'  => true,
                        'some_route' => false
                    ]
                ]
            ],
            $this->configurationProvider->getNavigationElements()
        );
    }

    public function testTitles()
    {
        $bundle1 = new BarBundle();
        $bundle2 = new FooBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);

        $this->assertEquals('Calls', $this->configurationProvider->getTitle('oro_call_index'));
        $this->assertNull($this->configurationProvider->getTitle('unknown'));
    }
}
