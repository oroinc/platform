<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Assetic;

use Oro\Bundle\LayoutBundle\Assetic\LayoutResource;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Testing\TempDirExtension;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class LayoutResourceTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var LayoutResource */
    protected $layoutResource;

    /** @var ThemeManager */
    protected $themeManager;

    /** @var string */
    protected $temporaryDir;

    protected function setUp()
    {
        $this->temporaryDir = $this->copyToTempDir(
            'LayoutResourceTest',
            __DIR__ . DIRECTORY_SEPARATOR . 'sample_data'
        );
        $this->layoutResource = new LayoutResource(
            $this->getThemeManager(),
            new Filesystem(),
            $this->temporaryDir
        );
        $this->layoutResource->setLogger($this->createMock(LoggerInterface::class));
    }

    /**
     * @return ThemeManager
     */
    protected function getThemeManager()
    {
        if (!$this->themeManager) {
            $this->themeManager = new ThemeManager(new ThemeFactory(), $this->getThemes());
        }

        return $this->themeManager;
    }

    /**
     * @return array
     */
    protected function getThemes()
    {
        $asset = [
            'inputs'  => ['styles.css', 'styles.scss', 'styles.less'],
            'filters' => ['filters'],
            'output'  => 'output.css',
        ];

        return [
            'without_assets'    => [],
            'with_empty_assets' => [
                'config' => ['assets' => []],
            ],
            'with_one_asset'    => [
                'config' => [
                    'assets' => [
                        'first' => $asset,
                    ]
                ],
            ],
            'with_two_asset'    => [
                'config' => [
                    'assets' => [
                        'first'  => $asset,
                        'second' => $asset,
                    ]
                ],
            ],
            'with_parent'       => [
                'parent' => 'parent',
                'config' => [
                    'assets' => [
                        'first' => $asset,
                    ]
                ],
            ],
            'parent'            => [
                'config' => [
                    'assets' => [
                        'first' => ['inputs' => ['parent_styles.css']],
                    ]
                ],
            ],
        ];
    }

    public function testIsFresh()
    {
        $now = time();
        self::assertFalse($this->layoutResource->isFresh($now + 1000));
        self::assertTrue($this->layoutResource->isFresh($now - 1000));
    }

    public function testToString()
    {
        self::assertEquals('layout', (string) $this->layoutResource);
    }

    public function testGetContent()
    {
        $themes = $this->getThemes();
        $formulae = [];
        foreach ($themes as $themeName => $theme) {
            if (!isset($theme['config']) || !isset($theme['config']['assets']) || empty($theme['config']['assets'])) {
                continue;
            }

            $assets = $theme['config']['assets'];
            if (isset($theme['parent'])) {
                $assets = array_merge_recursive($themes[$theme['parent']]['config']['assets'], $assets);
            }
            foreach ($assets as $assetKey => $asset) {
                if (!isset($asset['output']) || empty($asset['inputs'])) {
                    continue;
                }

                sort($asset['inputs']);

                $name = 'layout_' . $themeName . '_' . $assetKey;
                $formulae[$name] = [
                    $asset['inputs'],
                    $asset['filters'],
                    [
                        'output' => $asset['output'],
                        'name'   => $name,
                    ],
                ];
            }
        }

        self::assertArrayHasKey('layout_with_one_asset_first', $formulae);
        self::assertArrayHasKey('layout_with_two_asset_first', $formulae);
        self::assertArrayHasKey('layout_with_two_asset_second', $formulae);
        self::assertEquals($formulae, $this->layoutResource->getContent());
    }

    public function testOverwritingStylesInChildTheme()
    {
        $themes = [
            'parent_theme' => [
                'config' => [
                    'assets' => [
                        'styles' => [
                            'inputs' => [
                                'parent-style1.css',
                                'parent-style2.css',
                                'parent-style4.css',
                                'parent-style3.css'
                            ]
                        ]
                    ]
                ]
            ],
            'child_theme'  => [
                'parent' => 'parent_theme',
                'config' => [
                    'assets' => [
                        'styles' => [
                            'inputs'  => [
                                'child-style1.css',
                                ['parent-style2.css' => 'child-style2.css'],
                                ['parent-style4.css' => null],
                                'child-style3.css'
                            ],
                            'output'  => 'output.css',
                            'filters' => ['filters'],
                        ]
                    ]
                ]
            ],
        ];

        $this->themeManager = new ThemeManager(new ThemeFactory(), $themes);
        $this->layoutResource = new LayoutResource($this->themeManager, new Filesystem(), $this->temporaryDir);

        $expectedContentAfterMergingThemes = [
            'layout_child_theme_styles' => [
                [
                    'parent-style1.css',
                    'child-style2.css',
                    'parent-style3.css',
                    'child-style1.css',
                    'child-style3.css',
                ],
                [
                    'filters'
                ],
                [
                    'output' => 'output.css',
                    'name'   => 'layout_child_theme_styles'
                ]
            ]
        ];

        $content = $this->layoutResource->getContent();
        self::assertEquals($expectedContentAfterMergingThemes, $content);
    }

    public function testOverwritingStylesInChildThemeFromFile()
    {
        $expectedContentAfterMergingThemes = [
            'layout_custom_styles' => [
                [
                    'my_sidebar2.css',
                    'my_variables.css',
                    'my_custom_styles.css',
                    'my_forms.css',
                ],
                [
                    'filters'
                ],
                [
                    'output' => 'output.css',
                    'name'   => 'layout_custom_styles'
                ]
            ]
        ];

        $themes = Yaml::parse(file_get_contents($this->temporaryDir.DIRECTORY_SEPARATOR.'assets.yml'));
        $this->themeManager = new ThemeManager(new ThemeFactory(), $themes);
        $this->layoutResource = new LayoutResource($this->themeManager, new Filesystem(), $this->temporaryDir);
        $content = $this->layoutResource->getContent();
        self::assertEquals($expectedContentAfterMergingThemes, $content);
    }
}
