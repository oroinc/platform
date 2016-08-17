<?php

namespace LayoutBundle\Tests\Unit\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Prophecy\Prophecy\ObjectProphecy;

use Oro\Bundle\LayoutBundle\Model\ThemeImageType;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\ImageFilterProvider;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;

class ImageFilterProviderTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_ORIGINAL = 'product_original';
    const PRODUCT_LARGE = 'product_large';
    const PRODUCT_SMALL = 'product_small';
    const LARGE_SIZE = 378;
    const SMALL_SIZE = 56;

    /**
     * @var ImageFilterProvider
     */
    protected $imageFilterProvider;

    /**
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var FilterConfiguration
     */
    protected $filterConfig;

    /**
     * @var FilterConfiguration
     */
    protected $configManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct()
    {
        $this->imageTypeProvider = $this->prophesize(ImageTypeProvider::class);
        $this->filterConfig = $this->prophesize(FilterConfiguration::class);
        $this->configManager = $this->prophesize(ConfigManager::class);
        $this->filterConfig = $this->prophesize(FilterConfiguration::class);
        $this->doctrineHelper = $this->prophesize(DoctrineHelper::class);

        $this->imageFilterProvider = new ImageFilterProvider(
            $this->imageTypeProvider->reveal(),
            $this->filterConfig->reveal(),
            $this->configManager->reveal(),
            $this->doctrineHelper->reveal()
        );
    }

    public function testLoad()
    {
        $productOriginal = new ThemeImageTypeDimension(self::PRODUCT_ORIGINAL, null, null);
        $productLarge = new ThemeImageTypeDimension(self::PRODUCT_LARGE, self::LARGE_SIZE, self::LARGE_SIZE);
        $productSmall = new ThemeImageTypeDimension(self::PRODUCT_SMALL, self::SMALL_SIZE, self::SMALL_SIZE);

        $this->imageTypeProvider->getImageTypes()->willReturn([
            $this->prepareImageType([$productOriginal, $productLarge, $productSmall]),
            $this->prepareImageType([$productLarge, $productSmall]),
            $this->prepareImageType([$productOriginal, $productLarge, $productSmall])
        ]);

        $this->filterConfig->set(self::PRODUCT_ORIGINAL, $this->prepareFilterData())->shouldBeCalledTimes(1);
        $this->filterConfig
            ->set(self::PRODUCT_LARGE, $this->prepareFilterData(self::LARGE_SIZE, self::LARGE_SIZE))
            ->shouldBeCalledTimes(1);
        $this->filterConfig
            ->set(self::PRODUCT_SMALL, $this->prepareFilterData(self::SMALL_SIZE, self::SMALL_SIZE))
            ->shouldBeCalledTimes(1);

        $this->imageFilterProvider->load();
    }

    /**
     * @param ThemeImageTypeDimension[] $dimensions
     * @return ThemeImageType
     */
    private function prepareImageType(array $dimensions)
    {
        return new ThemeImageType('type', 'Type', $dimensions);
    }

    /**
     * @param int|null $width
     * @param int|null $height
     * @return array
     */
    private function prepareFilterData($width = null, $height = null)
    {
        $filterData = [
            'quality' => ImageFilterProvider::IMAGE_QUALITY,
            'filters' => [
                'strip' => []
            ]
        ];

        if ($width && $height) {
            $filterData = array_merge_recursive(
                $filterData,
                [
                    'filters' => [
                        'thumbnail' => [
                            'size' => [$width, $height],
                            'mode' => ImageFilterProvider::RESIZE_MODE,
                            'allow_upscale' => true
                        ],
                        'background' => [
                            'size' => [$width, $height],
                            'color' => ImageFilterProvider::BACKGROUND_COLOR
                        ]
                    ]
                ]
            );
        }

        return $filterData;
    }
}
