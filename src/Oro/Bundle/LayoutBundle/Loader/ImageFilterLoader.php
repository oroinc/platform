<?php

namespace Oro\Bundle\LayoutBundle\Loader;

use Imagine\Image\ImageInterface;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\CustomImageFilterProviderInterface;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;

/**
 * Load dimensions from theme config to LiipImagine filters
 */
class ImageFilterLoader
{
    const IMAGE_QUALITY = 95;
    const BACKGROUND_COLOR = '#fff';
    const RESIZE_MODE = ImageInterface::THUMBNAIL_INSET;

    /** @var ImageTypeProvider */
    protected $imageTypeProvider;

    /** @var FilterConfiguration */
    protected $filterConfiguration;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    /** @var CustomImageFilterProviderInterface[]  */
    protected $customFilterProviders = [];

    /**
     * @param ImageTypeProvider $imageTypeProvider
     * @param FilterConfiguration $filterConfiguration
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ImageTypeProvider $imageTypeProvider,
        FilterConfiguration $filterConfiguration,
        DoctrineHelper $doctrineHelper
    ) {
        $this->imageTypeProvider = $imageTypeProvider;
        $this->filterConfiguration = $filterConfiguration;
        $this->doctrineHelper = $doctrineHelper;
    }

    public function load()
    {
        foreach ($this->getAllDimensions() as $dimension) {
            $filterName = $dimension->getName();
            $this->filterConfiguration->set(
                $filterName,
                $this->buildFilterFromDimension($dimension)
            );
        }
    }

    /**
     * @param CustomImageFilterProviderInterface $provider
     */
    public function addCustomImageFilterProvider(CustomImageFilterProviderInterface $provider)
    {
        $this->customFilterProviders[] = $provider;
    }

    /**
     * @return ThemeImageTypeDimension[]
     */
    private function getAllDimensions()
    {
        $dimensions = [];

        foreach ($this->imageTypeProvider->getImageTypes() as $imageType) {
            $dimensions = array_merge($dimensions, $imageType->getDimensions());
        }

        return $dimensions;
    }

    /**
     * @param ThemeImageTypeDimension $dimension
     * @return array
     */
    private function buildFilterFromDimension(ThemeImageTypeDimension $dimension)
    {
        $width = $dimension->getWidth();
        $height = $dimension->getHeight();
        $withResize = $dimension->getWidth() && $dimension->getHeight();

        $filterSettings = [
            'quality' => self::IMAGE_QUALITY,
            'filters' => [
                'strip' => [],
            ]
        ];

        foreach ($this->customFilterProviders as $provider) {
            $filterSettings = array_replace_recursive($filterSettings, $provider->getFilterConfig());
        }

        if ($withResize) {
            $filterSettings = array_merge_recursive(
                [
                    'filters' => [
                        'thumbnail' => [
                            'size' => [$width, $height],
                            'mode' => self::RESIZE_MODE,
                            'allow_upscale' => true
                        ],
                        'background' => [
                            'size' => [$width, $height],
                            'color' => self::BACKGROUND_COLOR
                        ]
                    ]
                ],
                $filterSettings
            );
        }

        return $filterSettings;
    }
}
