<?php

namespace Oro\Bundle\LayoutBundle\Loader;

use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\DependencyInjection\Configuration;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\CustomImageFilterProviderInterface;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;

/**
 * Load dimensions from theme config to LiipImagine filters
 */
class ImageFilterLoader
{
    const IMAGE_QUALITY = 85;
    const BACKGROUND_COLOR = '#fff';
    const RESIZE_MODE = ImageInterface::THUMBNAIL_INSET;
    const INTERLACE_MODE = 'line';

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
        foreach ($this->imageTypeProvider->getImageDimensions() as $dimension) {
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
     * @param ThemeImageTypeDimension $dimension
     * @return array
     */
    private function buildFilterFromDimension(ThemeImageTypeDimension $dimension)
    {
        $width = $dimension->getWidth();
        $height = $dimension->getHeight();

        $filterSettings = [
            'quality' => self::IMAGE_QUALITY,
            'filters' => [
                'strip' => [],
                'interlace' => [
                    'mode' => self::INTERLACE_MODE
                ]
            ]
        ];

        foreach ($this->customFilterProviders as $provider) {
            if ($provider->isApplicable($dimension)) {
                $filterSettings = array_replace_recursive($filterSettings, $provider->getFilterConfig());
            }
        }

        if ($width && $height) {
            $filterSettings = array_merge_recursive(
                ['filters' => $this->prepareResizeFilterSettings($width, $height)],
                $filterSettings
            );
        }

        return $filterSettings;
    }

    /**
     * @param mixed $width
     * @param mixed $height
     * @return array
     */
    private function prepareResizeFilterSettings($width, $height)
    {
        if (Configuration::AUTO === $width || Configuration::AUTO === $height) {
            return [
                'scale' => [
                    'dim' => [
                        Configuration::AUTO === $width ? null : $width,
                        Configuration::AUTO === $height ? null : $height,
                    ]
                ]
            ];
        }

        return [
            'thumbnail' => [
                'size' => [$width, $height],
                'mode' => self::RESIZE_MODE,
                'allow_upscale' => true
            ],
            'background' => [
                'size' => [$width, $height],
                'color' => self::BACKGROUND_COLOR
            ]
        ];
    }
}
