<?php

namespace Oro\Bundle\LayoutBundle\Provider;

use Imagine\Image\ImageInterface;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;

/**
 * Load dimensions from theme config to LiipImagine filters
 */
class ImageFilterProvider
{
    const IMAGE_QUALITY = 95;
    const BACKGROUND_COLOR = '#fff';
    const RESIZE_MODE = ImageInterface::THUMBNAIL_INSET;

    /** @var ImageTypeProvider */
    protected $imageTypeProvider;

    /** @var FilterConfiguration */
    protected $filterConfiguration;

    /** @var  ConfigManager */
    protected $configManager;

    /** @var  DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ImageTypeProvider $imageTypeProvider
     * @param FilterConfiguration $filterConfiguration
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        ImageTypeProvider $imageTypeProvider,
        FilterConfiguration $filterConfiguration,
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper
    ) {
        $this->imageTypeProvider = $imageTypeProvider;
        $this->filterConfiguration = $filterConfiguration;
        $this->configManager = $configManager;
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

        $filterSettings = array_merge_recursive($filterSettings, $this->getWatermarkFilterSettings());

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

    /**
     * @return array
     */
    private function getWatermarkFilterSettings()
    {
        $config = [];
        $fileConfigKey = 'orob2b_product.product_image_watermark_file';
        $sizeConfigKey = 'orob2b_product.product_image_watermark_size';
        $positionConfigKey = 'orob2b_product.product_image_watermark_position';

        $imageId = $this->configManager->get($fileConfigKey);
        $size = $this->configManager->get($sizeConfigKey);
        $position = $this->configManager->get($positionConfigKey);

        if ($imageId) {
            /** @var File $image */
            $image = $this->doctrineHelper->getEntityRepositoryForClass(File::class)->find($imageId);
            $filePath = 'attachment/' . $image->getFilename();

            $config = [
                'filters' => [
                    'watermark' => [
                        'image' => $filePath,
                        'size' => round($size / 100, 2),
                        'position' => $position
                    ]
                ]
            ];
        }

        return $config;
    }
}
