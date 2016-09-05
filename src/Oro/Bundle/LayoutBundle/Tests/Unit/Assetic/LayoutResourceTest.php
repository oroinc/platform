<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Assetic;

use Oro\Bundle\LayoutBundle\Assetic\LayoutResource;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\Model\ThemeFactory;

class LayoutResourceTest extends \PHPUnit_Framework_TestCase
{
    /** @var LayoutResource */
    protected $layoutResource;

    /** @var ThemeManager */
    protected $themeManager;

    protected function setUp()
    {
        $this->layoutResource = new LayoutResource($this->getThemeManager());
    }

    protected function tearDown()
    {
        unset($this->layoutResource, $this->themeManager);
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
            'inputs' => ['styles.css'],
            'filters' => ['filters'],
            'output' => 'output.css',
        ];

        return [
            'without_assets' => [],
            'with_empty_assets' => [
                'config' => ['assets' => []],
            ],
            'with_one_asset' => [
                'config' => [
                    'assets' => [
                        'first' => $asset,
                    ]
                ],
            ],
            'with_two_asset' => [
                'config' => [
                    'assets' => [
                        'first' => $asset,
                        'second' => $asset,
                    ]
                ],
            ],
            'with_parent' => [
                'parent' => 'parent',
                'config' => [
                    'assets' => [
                        'first' => $asset,
                    ]
                ],
            ],
            'parent' => [
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
        $this->assertTrue($this->layoutResource->isFresh(1));
    }

    public function testToString()
    {
        $this->assertEquals('layout', (string)$this->layoutResource);
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
                $name = 'layout_' . $themeName . '_' . $assetKey;
                $formulae[$name] = [
                    $asset['inputs'],
                    $asset['filters'],
                    [
                        'output' => $asset['output'],
                        'name' => $name,
                    ],
                ];
            }
        }

        $this->assertArrayHasKey('layout_with_one_asset_first', $formulae);
        $this->assertArrayHasKey('layout_with_two_asset_first', $formulae);
        $this->assertArrayHasKey('layout_with_two_asset_second', $formulae);
        $this->assertEquals($formulae, $this->layoutResource->getContent());
    }
}
