<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\SidebarBundle\DependencyInjection\OroSidebarExtension;
use Oro\Bundle\SidebarBundle\Tests\Unit\Fixtures;

class OroSidebarExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $configs, array $expectedThemeSettings)
    {
        $extension = new OroSidebarExtension();

        $container = new ContainerBuilder();
        $container->setParameter(
            'kernel.bundles',
            array(
                new Fixtures\FooBundle\FooBundle(),
                new Fixtures\BarBundle\BarBundle(),
            )
        );

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
                        'module' => 'widget/foo',
                        'placement' => 'left'
                    ),
                    'bar' => array(
                        'title' => 'Bar',
                        'icon' => 'bar.ico',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    )
                ),
            ),
            'override' => array(
                'configs' => array(
                    array(
                        'sidebar_widgets' => array(
                            'foo' => array(
                                'title' => 'Foo Extended'
                            )
                        )
                    )
                ),
                'expectedThemeSettings' => array(
                    'foo' => array(
                        'title' => 'Foo Extended',
                        'icon' => 'foo.ico',
                        'module' => 'widget/foo',
                        'placement' => 'left'

                    ),
                    'bar' => array(
                        'title' => 'Bar',
                        'icon' => 'bar.ico',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    )
                ),
            )
        );
    }
}
