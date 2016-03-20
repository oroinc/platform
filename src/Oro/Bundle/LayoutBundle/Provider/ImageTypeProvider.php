<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Oro\Component\Layout\Extension\Theme\Model\ThemeImageType;
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
            foreach ($theme->getImageTypes() as $imageType) {
                $this->imageTypes[$imageType->getName()] = $imageType;
            }
        }
    }
}
