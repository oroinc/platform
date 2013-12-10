<?php


namespace Oro\Bundle\ThemeBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

use Oro\Bundle\ThemeBundle\DependencyInjection\OroThemeExtension;
use Oro\Bundle\ThemeBundle\Tests\Unit\Fixtures;

class OroThemeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $configs, array $expectedThemeSettings, $expectedActiveTheme)
    {
        $extension = new OroThemeExtension();

        $container = new ContainerBuilder();
        $container->setParameter(
            'kernel.bundles',
            array(
                new Fixtures\FooBundle\FooBundle(),
                new Fixtures\BarBundle\BarBundle(),
            )
        );

        $extension->load($configs, $container);

        $registryDefinition = $container->getDefinition('oro_theme.registry');
        $this->assertEquals('%oro_theme.registry.class%', $registryDefinition->getClass());

        $this->assertEquals($expectedThemeSettings, $container->getParameter('oro_theme.settings'));

        $registryDefinitionMethodCalls = $registryDefinition->getMethodCalls();
        if ($expectedActiveTheme) {
            $this->assertCount(1, $registryDefinitionMethodCalls);
            $this->assertEquals(
                array('setActiveTheme', array($expectedActiveTheme)),
                $registryDefinitionMethodCalls[0]
            );
        } else {
            $this->assertCount(0, $registryDefinitionMethodCalls);
        }

        $this->assertNotNull($container->getDefinition('oro_theme.registry'));
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
                        'label' => 'Foo Theme',
                        'styles' => array('styles.css')
                    ),
                    'bar' => array(
                        'label' => 'Bar Theme',
                        'styles' => array('styles.css')
                    )
                ),
                'expectedActiveTheme' => null
            ),
            'override' => array(
                'configs' => array(
                    array(
                        'active_theme' => 'foo',
                        'themes' => array(
                            'foo' => array(
                                'label' => 'Foo Extended Theme'
                            )
                        )
                    )
                ),
                'expectedThemeSettings' => array(
                    'foo' => array(
                        'label' => 'Foo Extended Theme',
                        'styles' => array('styles.css')
                    ),
                    'bar' => array(
                        'label' => 'Bar Theme',
                        'styles' => array('styles.css')
                    )
                ),
                'expectedActiveTheme' => 'foo'
            )
        );
    }
}
