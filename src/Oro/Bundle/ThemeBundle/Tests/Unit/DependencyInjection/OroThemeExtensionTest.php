<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ThemeBundle\DependencyInjection\OroThemeExtension;
use Oro\Bundle\ThemeBundle\Tests\Unit\Fixtures;
use Oro\Component\Config\CumulativeResourceManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroThemeExtensionTest extends TestCase
{
    /**
     * @dataProvider loadDataProvider
     */
    public function testLoad(
        array $configs,
        array $expectedThemeSettings,
        ?string $expectedActiveTheme,
        array $expectedSettings
    ): void {
        $bundle1 = new Fixtures\FooBundle\FooBundle();
        $bundle2 = new Fixtures\BarBundle\BarBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([$bundle1->getName() => get_class($bundle1), $bundle2->getName() => get_class($bundle2)]);

        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'prod');

        $extension = new OroThemeExtension();
        $extension->load($configs, $container);

        $registryDefinition = $container->getDefinition('oro_theme.registry');

        self::assertEquals($expectedThemeSettings, $container->getParameter('oro_theme.settings'));

        $registryDefinitionMethodCalls = $registryDefinition->getMethodCalls();
        if ($expectedActiveTheme) {
            self::assertCount(1, $registryDefinitionMethodCalls);
            self::assertEquals(
                ['setActiveTheme', [$expectedActiveTheme]],
                $registryDefinitionMethodCalls[0]
            );
        } else {
            self::assertCount(0, $registryDefinitionMethodCalls);
        }

        self::assertNotNull($container->getDefinition('oro_theme.registry'));
        self::assertNotNull($container->getDefinition('oro_theme.twig.extension'));
        self::assertEquals($expectedSettings, $container->getExtensionConfig('oro_theme'));
    }

    public function loadDataProvider(): array
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
                'expectedActiveTheme' => null,
                'expectedSettings' => [
                    [
                        'settings' => [
                            'resolved' => true,
                            'theme_configuration' => ['value' => null, 'scope' => 'app'],
                        ],
                    ],
                ],
            ],
            'override' => [
                'configs' => [
                    [
                        'active_theme' => 'foo',
                        'themes' => [
                            'foo' => [
                                'label' => 'Foo Extended Theme'
                            ]
                        ],
                        'settings' => [
                            'theme_configuration' => ['value' => 123],
                        ],
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
                'expectedActiveTheme' => 'foo',
                'expectedSettings' => [
                    [
                        'settings' => [
                            'resolved' => true,
                            'theme_configuration' => ['value' => 123, 'scope' => 'app'],
                        ],
                    ],
                ],
            ]
        ];
    }
}
