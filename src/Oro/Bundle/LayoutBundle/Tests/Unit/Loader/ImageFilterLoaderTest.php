<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Loader;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\CustomImageFilterProviderInterface;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;
use PHPUnit\Framework\MockObject\MockObject;

class ImageFilterLoaderTest extends \PHPUnit\Framework\TestCase
{
    const PRODUCT_ORIGINAL = 'product_original';
    const PRODUCT_LARGE = 'product_large';
    const PRODUCT_SMALL = 'product_small';
    const PRODUCT_GALLERY_MAIN = 'product_gallery_main';
    const LARGE_SIZE = 378;
    const SMALL_SIZE = 56;

    /** @var ImageFilterLoader */
    protected $imageFilterLoader;

    /** @var ImageTypeProvider|MockObject */
    protected $imageTypeProvider;

    /** @var FilterConfiguration|MockObject */
    protected $filterConfig;

    /** @var DoctrineHelper|MockObject */
    protected $doctrineHelper;

    protected function setUp(): void
    {
        $this->imageTypeProvider = $this->createMock(ImageTypeProvider::class);
        $this->filterConfig = $this->createMock(FilterConfiguration::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->imageFilterLoader = new ImageFilterLoader(
            $this->imageTypeProvider,
            $this->filterConfig,
            $this->doctrineHelper
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

        $customFilterProvider1 = $this->createMock(CustomImageFilterProviderInterface::class);
        $customFilterProvider1->method('isApplicable')->willReturn(true);
        $customFilterProvider1->method('getFilterConfig')->willReturn(['customFilterData']);
        $customFilterProvider2 = $this->createMock(CustomImageFilterProviderInterface::class);
        $customFilterProvider2->method('isApplicable')->willReturn(false);
        $customFilterProvider2->expects(static::never())->method('getFilterConfig');

        $this->imageFilterLoader->addCustomImageFilterProvider($customFilterProvider1);
        $this->imageFilterLoader->addCustomImageFilterProvider($customFilterProvider2);

        $this->imageTypeProvider->method('getImageDimensions')
            ->willReturn([
                self::PRODUCT_ORIGINAL => $productOriginal,
                self::PRODUCT_LARGE => $productLarge,
                self::PRODUCT_SMALL => $productSmall,
                self::PRODUCT_GALLERY_MAIN => $productGalleryMain
            ]);

        $this->filterConfig->expects(static::exactly(4))
            ->method('set')
            ->withConsecutive(
                [self::PRODUCT_ORIGINAL, $this->prepareBaseFilterData()],
                [self::PRODUCT_LARGE, $this->prepareFilterDataForResize(self::LARGE_SIZE, self::LARGE_SIZE)],
                [self::PRODUCT_SMALL, $this->prepareFilterDataForResize(self::SMALL_SIZE, self::SMALL_SIZE)],
                [
                    self::PRODUCT_GALLERY_MAIN,
                    $this->prepareFilterDataForResizeWithAuto(self::SMALL_SIZE, ThemeConfiguration::AUTO)
                ]
            );

        $this->imageFilterLoader->load();
    }

    public function testLoadWhenNoNewCustomImageFilterProviderAdded()
    {
        $this->imageTypeProvider->expects(static::exactly(2))->method('getImageDimensions')->willReturn([]);

        $this->imageFilterLoader->load();

        $customFilterProvider = $this->createMock(CustomImageFilterProviderInterface::class);
        $this->imageFilterLoader->addCustomImageFilterProvider($customFilterProvider);

        $this->imageFilterLoader->load();
    }

    public function testForceLoad()
    {
        $this->imageTypeProvider->expects(static::exactly(2))->method('getImageDimensions')->willReturn([]);

        $this->imageFilterLoader->load();

        // Try to force load configuration again when nothing has changed
        $this->imageFilterLoader->forceLoad();
    }

    public function testLoadWhenNewCustomImageFilterProviderAdded()
    {
        $this->imageTypeProvider->expects(static::once())->method('getImageDimensions')->willReturn([]);

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
