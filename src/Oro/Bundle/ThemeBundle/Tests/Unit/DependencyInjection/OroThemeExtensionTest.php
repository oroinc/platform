<?php


namespace Oro\Bundle\ThemeBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ThemeBundle\DependencyInjection\OroThemeExtension;
use Oro\Bundle\ThemeBundle\Tests\Unit\Fixtures;

use Oro\Component\Config\CumulativeResourceManager;

class OroThemeExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(array $configs, array $expectedThemeSettings, $expectedActiveTheme)
    {
        $bundle1 = new Fixtures\FooBundle\FooBundle();
        $bundle2 = new Fixtures\BarBundle\BarBundle();

        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)]);

        $extension = new OroThemeExtension();

        $container = new ContainerBuilder();

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
        $this->assertNotNull($container->getDefinition('oro_theme.twig.extension'));
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
