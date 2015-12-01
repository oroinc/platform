<?php

namespace Oro\Bundle\LayoutBundle\Assetic;

use Assetic\Factory\Resource\ResourceInterface;

use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

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
            $formulae += $this->collectThemeAssets($theme);
        }
        return $formulae;
    }

    /**
     * @param Theme $theme
     * @return array
     */
    protected function collectThemeAssets(Theme $theme)
    {
        $formulae = [];
        $assets = $theme->getDataByKey('assets', []);
        foreach ($assets as $assetKey => $asset) {
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
}
