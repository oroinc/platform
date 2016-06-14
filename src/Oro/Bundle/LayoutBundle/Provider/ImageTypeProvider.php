<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ImageTypeProvider
{
    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var ThemeImageType[]
     */
    protected $imageTypes;

    /**
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * @return ThemeImageType[]
     */
    public function getImageTypes()
    {
        if (!$this->imageTypes) {
            $this->collectImageTypesFromThemes();
        }

        return $this->imageTypes;
    }

    protected function collectImageTypesFromThemes()
    {
        $themes = $this->themeManager->getAllThemes();

        foreach ($themes as $theme) {
            foreach ($this->extractImageTypes($theme) as $imageType) {
                $this->imageTypes[$imageType->getName()] = $imageType;
            }
        }
    }

    /**
     * @param Theme $theme
     * @return ThemeImageType[]
     */
    protected function extractImageTypes(Theme $theme)
    {
        $config = $theme->getDataByKey('images', ['types' => []])['types'];
        $types = [];

        foreach ($config as $name => $type) {
            $dimensions = $type['dimensions'] ?: [];
            $types[] = new ThemeImageType($name, $type['label'], $dimensions, $type['max_number']);
        }

        return $types;
    }
}
