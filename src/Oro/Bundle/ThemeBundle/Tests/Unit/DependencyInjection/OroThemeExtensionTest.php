<?php


namespace Oro\Bundle\ThemeBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ThemeBundle\DependencyInjection\OroThemeExtension;
use Oro\Bundle\ThemeBundle\Tests\Unit\Fixtures;
use Oro\Component\Config\CumulativeResourceManager;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroThemeExtensionTest extends \PHPUnit\Framework\TestCase
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
                ['setActiveTheme', [$expectedActiveTheme]],
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
        return [
            'basic' => [
                'configs' => [
                    []
                ],
                'expectedThemeSettings' => [
                    'foo' => [
                        'label' => 'Foo Theme',
                    ],
                    'bar' => [
                        'label' => 'Bar Theme',
                    ]
                ],
                'expectedActiveTheme' => null
            ],
            'override' => [
                'configs' => [
                    [
                        'active_theme' => 'foo',
                        'themes' => [
                            'foo' => [
                                'label' => 'Foo Extended Theme'
                            ]
                        ]
                    ]
                ],
                'expectedThemeSettings' => [
                    'foo' => [
                        'label' => 'Foo Extended Theme',
                    ],
                    'bar' => [
                        'label' => 'Bar Theme',
                    ]
                ],
                'expectedActiveTheme' => 'foo'
            ]
        ];
    }
}
