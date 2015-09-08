<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\NavigationBundle\DependencyInjection\OroNavigationExtension;
use Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\BarBundle\BarBundle;
use Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\FooBundle\FooBundle;

use Oro\Component\Config\CumulativeResourceManager;

class OroNavigationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroNavigationExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new OroNavigationExtension();
    }

    /**
     * @dataProvider loadConfigurationDataProvider
     */
    public function testLoadConfiguration(array $configs, array $bundles, array $expectedMenu, array $expectedTitles)
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles($bundles);

        $container = new ContainerBuilder();

        $this->extension->load($configs, $container);

        $this->assertTrue($container->hasDefinition('oro_menu.configuration_builder'));
        $menuBuilder = $container->getDefinition('oro_menu.configuration_builder');
        $data = $menuBuilder->getMethodCalls();
        $this->assertEquals(
            array(
                array(
                    'setConfiguration',
                    array($expectedMenu)
                )
            ),
            $data,
            'Unexpected menu for builder'
        );

        $this->assertTrue($container->hasDefinition('oro_menu.twig.extension'));
        $menuBuilder = $container->getDefinition('oro_menu.twig.extension');
        $data = $menuBuilder->getMethodCalls();

        $actualCall = end($data);
        $this->assertEquals(
            ['setMenuConfiguration', [$expectedMenu]],
            $actualCall,
            'Unexpected menu for twig'
        );

        $this->assertTrue($container->hasDefinition('oro_navigation.title_config_reader'));
        $configReader = $container->getDefinition('oro_navigation.title_config_reader');
        $data = $configReader->getMethodCalls();
        $this->assertEquals(
            array(
                array(
                    'setConfigData',
                    array($expectedTitles)
                )
            ),
            $data
        );

        $this->assertTrue($container->hasDefinition('oro_navigation.title_provider'));
        $titleService = $container->getDefinition('oro_navigation.title_provider');
        $data = $titleService->getMethodCalls();
        $this->assertEquals(
            array(
                array(
                    'setTitles',
                    array($expectedTitles)
                )
            ),
            $data
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function loadConfigurationDataProvider()
    {
        $bundle1 = new BarBundle();
        $bundle2 = new FooBundle();

        $settings = array(
            'resolved' => true,
            'maxItems' => array(
                'value' => 20,
                'scope' => 'app'
            ),
            'title_suffix' => array(
                'value' => null,
                'scope' => 'app'
            ),
            'title_delimiter' => array(
                'value' => '-',
                'scope' => 'app'
            ),
            'breadcrumb_menu' => array(
                'value' => 'application_menu',
                'scope' => 'app'
            )
        );
        $defaultItemParameters = array(
            'translateParameters' => array(),
            'routeParameters' => array(),
            'extras' => array()
        );

        return array(
            'without_bundles' => array(
                'configs' => array(
                    array(
                        'items' => array(
                            'items_sub2' => array('label' => 'Sub2')
                        ),
                        'tree' => array(
                            'application_menu' => array(
                                'children' => array(
                                    'items_sub2' => null
                                )
                            )
                        )
                    )
                ),
                'bundles' => array(),
                'expectedMenu' => array(
                    'items' => array(
                        'items_sub2' => array_merge(array('label' => 'Sub2'), $defaultItemParameters)
                    ),
                    'tree' => array(
                        'application_menu' => array(
                            'children' => array(
                                'items_sub2' => array(
                                    'children' => array(),
                                    'merge_strategy' => 'append'
                                )
                            ),
                            'extras' => array(),
                        )
                    ),
                    'templates' => array(),
                    'settings' => $settings,
                    'oro_navigation_elements' => array()
                ),
                'expectedTitles' => array()
            ),
            'with_bundles' => array(
                'configs' => array(
                    array(
                        'items' => array(
                            'items_sub2' => array('label' => 'Sub2'),
                            'call_list' => array('label' => 'Calls RENAMED')
                        ),
                        'tree' => array(
                            'application_menu' => array(
                                'children' => array(
                                    'items_sub2' => array(
                                        'children' => array(
                                            'to_replace' => array(
                                                'merge_strategy' => 'replace'
                                            ),
                                            'to_move_top' => array(
                                                'merge_strategy' => 'move'
                                            ),
                                        )
                                    )
                                )
                            )
                        )
                    )
                ),
                'bundles' => array(
                    $bundle1->getName() => get_class($bundle1),
                    $bundle2->getName() => get_class($bundle2),
                ),
                'expectedMenu' => array(
                    'items' => array(
                        'customers_tab' => array_merge(array('label' => 'Customers'), $defaultItemParameters),
                        'call_list' => array_merge(array('label' => 'Calls RENAMED'), $defaultItemParameters),
                        'to_replace' => array_merge(array('label' => 'Replaced'), $defaultItemParameters),
                        'to_move_top' => array_merge(array('label' => 'To move'), $defaultItemParameters),
                        'to_move_child' => array_merge(array('label' => 'To move child'), $defaultItemParameters),
                        'shortcut_call_list' => array_merge(array('label' => 'Show list'), $defaultItemParameters),
                        'items_sub2' => array_merge(array('label' => 'Sub2'), $defaultItemParameters)
                    ),
                    'tree' => array(
                        'application_menu' => array(
                            'type' => 'application_menu',
                            'children' => array(
                                'customers_tab' => array(
                                    'children' => array(
                                        'call_list' => array(
                                            'children' => array(),
                                            'merge_strategy' => 'append'
                                        )
                                    ),
                                    'merge_strategy' => 'append'
                                ),
                                'items_sub2' => array(
                                    'children' => array(
                                        'to_replace' => array(
                                            'children' => array(),
                                            'merge_strategy' => 'replace'
                                        ),
                                        'to_move_top' => array(
                                            'children' => array(
                                                'to_move_child' => array(
                                                    'children' => array(),
                                                    'merge_strategy' => 'append'
                                                )
                                            ),
                                            'merge_strategy' => 'move'
                                        ),
                                    ),
                                    'merge_strategy' => 'append'
                                )
                            ),
                            'extras' => array(),
                        ),
                        'shortcuts' => array(
                            'type' => 'shortcuts',
                            'children' => array(
                                'shortcut_call_list' => array(
                                    'children' => array(),
                                    'merge_strategy' => 'append'
                                )
                            ),
                            'extras' => array(),
                        )
                    ),
                    'templates' => array(),
                    'settings'                => $settings,
                    'oro_navigation_elements' => array(
                        'favoriteButton' => array(
                            'default' => true,
                            'routes'  => array(
                                'call_list'  => false,
                                'some_route' => false
                            )
                        ),
                        'shortcutsPanel' => array(
                            'default' => true,
                            'routes'  => array(
                                'call_list'  => true,
                                'some_route' => false
                            )
                        ),
                    )
                ),
                'expectedTitles' => array(
                    'orocrm_call_index' => 'Calls'
                )
            )
        );
    }
}
