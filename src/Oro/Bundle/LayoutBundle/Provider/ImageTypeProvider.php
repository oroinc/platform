<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class ImageTypeProvider
{
    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var ThemeImageType[]
     */
    protected $imageTypes = [];

    /**
     * @var ThemeImageTypeDimension[]
     */
    protected $dimensions = [];

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

    /**
     * @return ThemeImageTypeDimension[]
     */
    public function getImageDimensions()
    {
        if (!$this->dimensions) {
            $this->collectDimensionsFromThemes($this->themeManager->getAllThemes());
        }

        return $this->dimensions;
    }

    protected function collectImageTypesFromThemes()
    {
        $themes = $this->themeManager->getAllThemes();
        $this->collectDimensionsFromThemes($themes);

        foreach ($themes as $theme) {
            foreach ($this->extractImageTypes($theme) as $imageType) {
                $imageTypeName = $imageType->getName();

                if (isset($this->imageTypes[$imageTypeName])) {
                    $imageType->mergeDimensions($this->imageTypes[$imageTypeName]->getDimensions());
                }

                $this->imageTypes[$imageTypeName] = $imageType;
            }
        }
    }

    /**
     * @param Theme $theme
     * @return ThemeImageType[]
     */
    protected function extractImageTypes(Theme $theme)
    {
        $config = $theme->getConfigByKey('images', ['types' => []])['types'];
        $types = [];

        foreach ($config as $name => $type) {
            $dimensions = $this->getDimensionsForType($type);
            $types[] = new ThemeImageType($name, $type['label'], $dimensions, $type['max_number']);
        }

        return $types;
    }

    /**
     * @param Theme[] $themes
     */
    protected function collectDimensionsFromThemes(array $themes)
    {
        foreach ($themes as $theme) {
            foreach ($this->extractDimensions($theme) as $name => $dimension) {
                $this->dimensions[$name] = new ThemeImageTypeDimension(
                    $name,
                    $dimension['width'],
                    $dimension['height'],
                    array_key_exists('options', $dimension) ? $dimension['options'] : null
                );
            }
        }
    }

    /**
     * @param Theme $theme
     * @return ThemeImageType[]
     */
    protected function extractDimensions(Theme $theme)
    {
        return $theme->getConfigByKey('images', ['dimensions' => []])['dimensions'];
    }

    /**
     * @param array $type
     * @return ThemeImageTypeDimension[]
     */
    protected function getDimensionsForType(array $type)
    {
        $dimensions = [];

        foreach (($type['dimensions'] ?: []) as $dimensionName) {
            if (!isset($this->dimensions[$dimensionName])) {
                throw new InvalidConfigurationException(
                    sprintf(
                        'Unable to find dimension named "%s"',
                        $dimensionName
                    )
                );
            }

            $dimensions[$dimensionName] = $this->dimensions[$dimensionName];
        }

        return $dimensions;
    }

    /**
     * Get maximum number by types array
     *
     * @return array
     */
    public function getMaxNumberByType()
    {
        $maxNumbers = [];

        foreach ($this->getImageTypes() as $imageType) {
            $maxNumbers[$imageType->getName()] = [
                'max' => $imageType->getMaxNumber(),
                'label' => $imageType->getLabel()
            ];
        }

        return $maxNumbers;
    }
}
