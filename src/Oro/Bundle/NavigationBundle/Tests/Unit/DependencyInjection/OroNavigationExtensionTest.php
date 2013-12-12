<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\NavigationBundle\DependencyInjection\OroNavigationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

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
        $container = $this->createContainer($bundles);

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
            $data
        );

        $this->assertTrue($container->hasDefinition('oro_menu.twig.extension'));
        $menuBuilder = $container->getDefinition('oro_menu.twig.extension');
        $data = $menuBuilder->getMethodCalls();
        $this->assertEquals(
            array(
                array(
                    'setMenuConfiguration',
                    array($expectedMenu)
                )
            ),
            $data
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

        $this->assertTrue($container->hasDefinition('oro_navigation.title_service'));
        $titleService = $container->getDefinition('oro_navigation.title_service');
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
        $settings = array(
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
                                'items_sub2' => array('children' => array())
                            ),
                            'extras' => array(),
                        )
                    ),
                    'templates' => array(),
                    'settings' => $settings
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
                                    'items_sub2' => null
                                )
                            )
                        )
                    )
                ),
                'bundles' => array(
                    'Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\BarBundle\BarBundle',
                    'Oro\Bundle\NavigationBundle\Tests\Unit\DependencyInjection\Fixtures\FooBundle\FooBundle',
                ),
                'expectedMenu' => array(
                    'items' => array(
                        'customers_tab' => array_merge(array('label' => 'Customers'), $defaultItemParameters),
                        'call_list' => array_merge(array('label' => 'Calls RENAMED'), $defaultItemParameters),
                        'shortcut_call_list' => array_merge(array('label' => 'Show list'), $defaultItemParameters),
                        'items_sub2' => array_merge(array('label' => 'Sub2'), $defaultItemParameters)
                    ),
                    'tree' => array(
                        'application_menu' => array(
                            'type' => 'application_menu',
                            'children' => array(
                                'customers_tab' => array(
                                    'children' => array(
                                        'call_list' => array('children' => array())
                                    )
                                ),
                                'items_sub2' => array('children' => array())
                            ),
                            'extras' => array(),
                        ),
                        'shortcuts' => array(
                            'type' => 'shortcuts',
                            'children' => array(
                                'shortcut_call_list' => array('children' => array())
                            ),
                            'extras' => array(),
                        )
                    ),
                    'templates' => array(),
                    'settings' => $settings
                ),
                'expectedTitles' => array(
                    'orocrm_call_index' => 'Calls'
                )
            )
        );
    }

    protected function createContainer(array $bundles = array())
    {
        $container = new ContainerBuilder(
            new ParameterBag(
                array(
                    'kernel.bundles'=> $bundles,
                )
            )
        );

        return $container;
    }
}
