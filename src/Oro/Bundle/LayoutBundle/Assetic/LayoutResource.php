<?php

namespace Oro\Bundle\LayoutBundle\Assetic;

use Assetic\Factory\Resource\ResourceInterface;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\PhpUtils\ArrayUtil;

class LayoutResource implements ResourceInterface
{
    const RESOURCE_ALIAS = 'layout';

    /** @var ThemeManager */
    protected $themeManager;

    /**
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * @inheritdoc
     */
    public function isFresh($timestamp)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return self::RESOURCE_ALIAS;
    }

    /**
     * @inheritdoc
     */
    public function getContent()
    {
        $formulae = [];
        $themes = $this->themeManager->getAllThemes();
        foreach ($themes as $theme) {
            $formulae += $this->collectThemeFormulae($theme);
        }
        return $formulae;
    }

    /**
     * @param Theme $theme
     * @return array
     */
    protected function collectThemeFormulae(Theme $theme)
    {
        $formulae = [];
        $assets = $this->collectThemeAssets($theme);
        foreach ($assets as $assetKey => $asset) {
            if (!isset($asset['output']) || empty($asset['inputs'])) {
                continue;
            }
            $name = self::RESOURCE_ALIAS . '_' . $theme->getName(). '_' . $assetKey;
            $formulae[$name] = [
                $asset['inputs'],
                $asset['filters'],
                [
                    'output' => $asset['output'],
                    'name' => $name,
                ],
            ];
        }
        return $formulae;
    }

    /**
     * @param Theme $theme
     * @return array
     */
    protected function collectThemeAssets(Theme $theme)
    {
        $assets = $theme->getDataByKey('assets', []);

        $parentTheme = $theme->getParentTheme();
        if ($parentTheme) {
            $parentTheme = $this->themeManager->getTheme($parentTheme);
            $assets = ArrayUtil::arrayMergeRecursiveDistinct($this->collectThemeAssets($parentTheme), $assets);
        }

        return $assets;
    }
}
