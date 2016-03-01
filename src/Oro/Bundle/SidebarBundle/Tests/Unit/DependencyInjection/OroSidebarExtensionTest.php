<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\SidebarBundle\DependencyInjection\OroSidebarExtension;
use Oro\Bundle\SidebarBundle\Tests\Unit\Fixtures;

use Oro\Component\Config\CumulativeResourceManager;

class OroSidebarExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $configs, array $expectedThemeSettings)
    {
        $bundle1 = new Fixtures\FooBundle\FooBundle();
        $bundle2 = new Fixtures\BarBundle\BarBundle();

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)]);

        $extension = new OroSidebarExtension();

        $container = new ContainerBuilder();

        $extension->load($configs, $container);

        $this->assertEquals(
            $expectedThemeSettings,
            $container->getParameter(OroSidebarExtension::WIDGETS_SETTINGS_PARAMETER)
        );

        $this->assertNotNull($container->getDefinition('oro_sidebar.widget_definition.registry'));
        $this->assertNotNull($container->getDefinition('oro_sidebar.twig.extension'));
    }

    public function loadDataProvider()
    {
        return array(
            'basic' => array(
                'configs' => array(
                    array()
                ),
                'expectedThemeSettings' => array(
                    'foo' => array(
                        'title' => 'Foo',
                        'icon' => 'foo.ico',
                        'iconClass' => null,
                        'module' => 'widget/foo',
                        'placement' => 'left',
                        'settings' => array('test' => 'Hello'),
                        'showRefreshButton' => true
                    ),
                    'bar' => array(
                        'title' => 'Bar',
                        'icon' => null,
                        'iconClass' => 'test',
                        'module' => 'widget/bar',
                        'placement' => 'both',
                        'settings' => null,
                        'showRefreshButton' => true
                    )
                ),
            ),
            'override' => array(
                'configs' => array(
                    array(
                        'sidebar_widgets' => array(
                            'foo' => array(
                                'title' => 'Foo Extended',
                                'settings' => array('test2' => 'Rewritten'),
                                'icon' => null,
                                'iconClass' => 'test2'
                            )
                        )
                    )
                ),
                'expectedThemeSettings' => array(
                    'foo' => array(
                        'title' => 'Foo Extended',
                        'icon' => null,
                        'iconClass' => 'test2',
                        'module' => 'widget/foo',
                        'placement' => 'left',
                        'settings' => array('test2' => 'Rewritten'),
                        'showRefreshButton' => true

                    ),
                    'bar' => array(
                        'title' => 'Bar',
                        'icon' => null,
                        'iconClass' => 'test',
                        'module' => 'widget/bar',
                        'placement' => 'both',
                        'settings' => null,
                        'showRefreshButton' => true
                    )
                ),
            )
        );
    }
}
