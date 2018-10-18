<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\NavigationBundle\Provider\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\BarBundle\BarBundle;
use Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\FooBundle\FooBundle;
use Oro\Component\Config\CumulativeResourceManager;

class ConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigurationProvider */
    protected $configurationProvider;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $cache;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->cache = $this->getMockBuilder(CacheProvider::class)->getMock();
        $this->configurationProvider = new ConfigurationProvider($this->cache);
    }

    /**
     * @dataProvider configurationDataProvider
     *
     * @param string $groupKey
     * @param array  $bundles
     * @param array  $expectedResult
     */
    public function testGetConfiguration($groupKey, array $bundles, array $expectedResult)
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles);

        $this->cache
            ->expects($this->once())
            ->method('save')
            ->with(ConfigurationProvider::CACHE_KEY);

        $this->cache
            ->expects($this->once())
            ->method('fetch')
            ->with(ConfigurationProvider::CACHE_KEY)
            ->willReturn(false);

        $this->assertEquals(
            $expectedResult,
            $this->configurationProvider->getConfiguration($groupKey)
        );

        // test load from cache
        $this->assertEquals(
            $expectedResult,
            $this->configurationProvider->getConfiguration($groupKey)
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configurationDataProvider()
    {
        $bundle1 = new BarBundle();
        $bundle2 = new FooBundle();

        $defaultItemParameters = [
            'translateParameters' => [],
            'routeParameters' => [],
            'extras' => [],
        ];

        return [
            'menu config' => [
                'groupKey' => ConfigurationProvider::MENU_CONFIG_KEY,
                'bundles' => [
                    $bundle1->getName() => get_class($bundle1),
                    $bundle2->getName() => get_class($bundle2),
                ],
                'expectedResult' => [
                    'items' => [
                        'customers_tab' => array_merge(
                            ['label' => 'Customers', 'read_only' => true],
                            $defaultItemParameters
                        ),
                        'call_list' => array_merge(['label' => 'Calls'], $defaultItemParameters),
                        'to_replace' => array_merge(['label' => 'To replace'], $defaultItemParameters),
                        'to_move_top' => array_merge(['label' => 'To move'], $defaultItemParameters),
                        'shortcut_call_list' => array_merge(['label' => 'Show list'], $defaultItemParameters),
                        'quicklinks_request_quote' => array_merge(
                            ['label' => 'Request Quote'],
                            $defaultItemParameters
                        ),
                        'default_tab' => array_merge(['label' => 'Default'], $defaultItemParameters),
                        'to_replace_child' => array_merge(['label' => 'To replace child'], $defaultItemParameters),
                        'to_move_top_child' => array_merge(['label' => 'To move top child'], $defaultItemParameters),
                        'to_replace_new_child' => array_merge(
                            ['label' => 'To replace new child'],
                            $defaultItemParameters
                        ),
                        'to_move_top_new_child' => array_merge(
                            ['label' => 'To move top new child'],
                            $defaultItemParameters
                        ),
                    ],
                    'tree' => [
                        'application_menu' => [
                            'type' => 'application_menu',
                            'scope_type' => 'default',
                            'max_nesting_level' => 1,
                            'children' => [
                                'customers_tab' => [
                                    'children' => [
                                        'call_list' => [
                                            'children' => [],
                                            'merge_strategy' => 'move',
                                        ],
                                        'to_replace' => [
                                            'children' => [
                                                'to_replace_new_child' => [
                                                    'children' => [],
                                                    'merge_strategy' => 'move',
                                                ],
                                            ],
                                            'merge_strategy' => 'replace',
                                        ],
                                        'to_move_top' => [
                                            'children' => [
                                                'to_move_top_child' => [
                                                    'children' => [],
                                                    'merge_strategy' => 'move',
                                                ],
                                                'to_move_top_new_child' => [
                                                    'children' => [],
                                                    'merge_strategy' => 'move',
                                                ],
                                            ],
                                            'merge_strategy' => 'move',
                                        ],
                                    ],
                                    'merge_strategy' => 'move',
                                ],
                                'default_tab' => [
                                    'children' => [],
                                    'merge_strategy' => 'move'
                                ],
                            ],
                            'extras' => [],
                        ],
                        'shortcuts' => [
                            'type' => 'shortcuts',
                            'scope_type' => 'custom',
                            'read_only' => true,
                            'children' => [
                                'shortcut_call_list' => [
                                    'children' => [],
                                    'merge_strategy' => 'move',
                                ]
                            ],
                            'extras' => [],
                        ],
                        'quicklinks' => [
                            'type' => 'quicklinks',
                            'children' => [
                                'quicklinks_request_quote' =>[
                                    'children' => [],
                                    'merge_strategy' => 'move',
                                ],
                            ],
                            'extras' => [],
                        ],
                    ],
                    'templates' => [],
                ]
            ],
            'empty menu config' => [
                'groupKey' => ConfigurationProvider::MENU_CONFIG_KEY,
                'bundles' => [],
                'expectedResult' => [
                    'items' => [],
                    'tree' => [],
                    'templates' => [],
                ]
            ],
            'navigation elements' => [
                'groupKey' => ConfigurationProvider::NAVIGATION_ELEMENTS_KEY,
                'bundles' => [
                    $bundle1->getName() => get_class($bundle1),
                    $bundle2->getName() => get_class($bundle2),
                ],
                'expectedResult' => [
                    'favoriteButton' => [
                        'default' => true,
                        'routes' => [
                            'call_list' => false,
                            'some_route' => false
                        ]
                    ],
                    'shortcutsPanel' => [
                        'default' => true,
                        'routes' => [
                            'call_list' => true,
                            'some_route' => false
                        ]
                    ],
                ]
            ],
            'empty navigation elements' => [
                'groupKey' => ConfigurationProvider::NAVIGATION_ELEMENTS_KEY,
                'bundles' => [],
                'expectedResult' => []
            ],
            'titles' => [
                'groupKey' => ConfigurationProvider::TITLES_KEY,
                'bundles' => [
                    $bundle1->getName() => get_class($bundle1),
                    $bundle2->getName() => get_class($bundle2),
                ],
                'expectedResult' => [
                    'oro_call_index' => 'Calls',
                ]
            ],
            'empty titles' => [
                'groupKey' => ConfigurationProvider::TITLES_KEY,
                'bundles' => [],
                'expectedResult' => []
            ]
        ];
    }
}
