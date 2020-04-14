<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Loader;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\CustomImageFilterProviderInterface;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use Prophecy\Argument;

class ImageFilterLoaderTest extends \PHPUnit\Framework\TestCase
{
    const PRODUCT_ORIGINAL = 'product_original';
    const PRODUCT_LARGE = 'product_large';
    const PRODUCT_SMALL = 'product_small';
    const PRODUCT_GALLERY_MAIN = 'product_gallery_main';
    const LARGE_SIZE = 378;
    const SMALL_SIZE = 56;

    /**
     * @var ImageFilterLoader
     */
    protected $imageFilterLoader;

    /**
     * @var ImageTypeProvider
     */
    protected $imageTypeProvider;

    /**
     * @var FilterConfiguration
     */
    protected $filterConfig;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    protected function setUp(): void
    {
        $this->imageTypeProvider = $this->prophesize(ImageTypeProvider::class);
        $this->filterConfig = $this->prophesize(FilterConfiguration::class);
        $this->doctrineHelper = $this->prophesize(DoctrineHelper::class);

        $this->imageFilterLoader = new ImageFilterLoader(
            $this->imageTypeProvider->reveal(),
            $this->filterConfig->reveal(),
            $this->doctrineHelper->reveal()
        );
    }

    public function testLoad()
    {
        $productOriginal = new ThemeImageTypeDimension(self::PRODUCT_ORIGINAL, null, null);
        $productLarge = new ThemeImageTypeDimension(self::PRODUCT_LARGE, self::LARGE_SIZE, self::LARGE_SIZE);
        $productSmall = new ThemeImageTypeDimension(self::PRODUCT_SMALL, self::SMALL_SIZE, self::SMALL_SIZE);
        $productGalleryMain = new ThemeImageTypeDimension(
            self::PRODUCT_GALLERY_MAIN,
            self::SMALL_SIZE,
            ThemeConfiguration::AUTO
        );

        $customFilterProvider1 = $this->prophesize(CustomImageFilterProviderInterface::class);
        $customFilterProvider1->isApplicable(Argument::any())->willReturn(true);
        $customFilterProvider1->getFilterConfig()->willReturn(['customFilterData']);
        $customFilterProvider2 = $this->prophesize(CustomImageFilterProviderInterface::class);
        $customFilterProvider2->isApplicable(Argument::any())->willReturn(false);
        $customFilterProvider2->getFilterConfig()->shouldNotBeCalled([]);

        $this->imageFilterLoader->addCustomImageFilterProvider($customFilterProvider1->reveal());
        $this->imageFilterLoader->addCustomImageFilterProvider($customFilterProvider2->reveal());

        $this->imageTypeProvider->getImageDimensions()->willReturn([
            self::PRODUCT_ORIGINAL => $productOriginal,
            self::PRODUCT_LARGE => $productLarge,
            self::PRODUCT_SMALL => $productSmall,
            self::PRODUCT_GALLERY_MAIN => $productGalleryMain
        ]);

        $this->filterConfig->set(self::PRODUCT_ORIGINAL, $this->prepareBaseFilterData())->shouldBeCalledTimes(1);
        $this->filterConfig
            ->set(self::PRODUCT_LARGE, $this->prepareFilterDataForResize(self::LARGE_SIZE, self::LARGE_SIZE))
            ->shouldBeCalledTimes(1);
        $this->filterConfig
            ->set(self::PRODUCT_SMALL, $this->prepareFilterDataForResize(self::SMALL_SIZE, self::SMALL_SIZE))
            ->shouldBeCalledTimes(1);
        $this->filterConfig
            ->set(self::PRODUCT_GALLERY_MAIN, $this->prepareFilterDataForResizeWithAuto(
                self::SMALL_SIZE,
                ThemeConfiguration::AUTO
            ))->shouldBeCalledTimes(1);

        $this->imageFilterLoader->load();
    }

    public function testLoadWhenNoNewCustomImageFilterProviderAdded()
    {
        $this->imageTypeProvider->getImageDimensions()->shouldBeCalledTimes(2)->willReturn([]);

        $this->imageFilterLoader->load();

        $customFilterProvider = $this->prophesize(CustomImageFilterProviderInterface::class);
        $this->imageFilterLoader->addCustomImageFilterProvider($customFilterProvider->reveal());

        $this->imageFilterLoader->load();
    }

    public function testForceLoad()
    {
        $this->imageTypeProvider->getImageDimensions()->shouldBeCalledTimes(2)->willReturn([]);

        $this->imageFilterLoader->load();

        // Try to force load configuration again when nothing has changed
        $this->imageFilterLoader->forceLoad();
    }

    public function testLoadWhenNewCustomImageFilterProviderAdded()
    {
        $this->imageTypeProvider->getImageDimensions()->shouldBeCalledTimes(1)->willReturn([]);

        $this->imageFilterLoader->load();

        // Try to load configuration again when nothing has changed
        $this->imageFilterLoader->load();
    }

    /**
     * @param int $width
     * @param int $height
     * @return array
     */
    private function prepareFilterDataForResize($width, $height)
    {
        $resizeFiltersData = [
            'thumbnail' => [
                'size' => [$width, $height],
                'mode' => ImageFilterLoader::RESIZE_MODE,
                'allow_upscale' => true
            ],
            'background' => [
                'size' => [$width, $height],
                'color' => ImageFilterLoader::BACKGROUND_COLOR
            ]
        ];

        return array_merge_recursive($this->prepareBaseFilterData(), ['filters' => $resizeFiltersData]);
    }

    /**
     * @param mixed $width
     * @param mixed $height
     * @return array
     */
    private function prepareFilterDataForResizeWithAuto($width, $height)
    {
        $resizeFiltersData = [
            'scale' => [
                'dim' => [
                    ThemeConfiguration::AUTO === $width ? null : $width,
                    ThemeConfiguration::AUTO === $height ? null : $height
                ]
            ]
        ];

        return array_merge_recursive($this->prepareBaseFilterData(), ['filters' => $resizeFiltersData]);
    }

    /**
     * @return array
     */
    private function prepareBaseFilterData()
    {
        return [
            'quality' => ImageFilterLoader::IMAGE_QUALITY,
            'filters' => [
                'strip' => [],
                'interlace' => [
                    'mode' => ImageFilterLoader::INTERLACE_MODE
                ]
            ],
            'customFilterData'
        ];
    }
}
