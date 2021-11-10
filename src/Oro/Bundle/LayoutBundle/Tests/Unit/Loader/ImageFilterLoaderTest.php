<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Loader;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\LayoutBundle\Loader\ImageFilterLoader;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\LayoutBundle\Provider\CustomImageFilterProviderInterface;
use Oro\Bundle\LayoutBundle\Provider\ImageTypeProvider;

class ImageFilterLoaderTest extends \PHPUnit\Framework\TestCase
{
    private const PRODUCT_ORIGINAL = 'product_original';
    private const PRODUCT_LARGE = 'product_large';
    private const PRODUCT_SMALL = 'product_small';
    private const PRODUCT_GALLERY_MAIN = 'product_gallery_main';
    private const LARGE_SIZE = 378;
    private const SMALL_SIZE = 56;

    /** @var ImageTypeProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $imageTypeProvider;

    /** @var FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject */
    private $filterConfig;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ImageFilterLoader */
    private $imageFilterLoader;

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
        $customFilterProvider1->expects(self::any())
            ->method('isApplicable')
            ->willReturn(true);
        $customFilterProvider1->expects(self::any())
            ->method('getFilterConfig')
            ->willReturn(['customFilterData']);
        $customFilterProvider2 = $this->createMock(CustomImageFilterProviderInterface::class);
        $customFilterProvider2->expects(self::any())
            ->method('isApplicable')
            ->willReturn(false);
        $customFilterProvider2->expects(self::never())
            ->method('getFilterConfig');

        $this->imageFilterLoader->addCustomImageFilterProvider($customFilterProvider1);
        $this->imageFilterLoader->addCustomImageFilterProvider($customFilterProvider2);

        $this->imageTypeProvider->expects(self::any())
            ->method('getImageDimensions')
            ->willReturn([
                self::PRODUCT_ORIGINAL => $productOriginal,
                self::PRODUCT_LARGE => $productLarge,
                self::PRODUCT_SMALL => $productSmall,
                self::PRODUCT_GALLERY_MAIN => $productGalleryMain
            ]);

        $this->filterConfig->expects(self::exactly(4))
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
        $this->imageTypeProvider->expects(self::exactly(2))
            ->method('getImageDimensions')
            ->willReturn([]);

        $this->imageFilterLoader->load();

        $customFilterProvider = $this->createMock(CustomImageFilterProviderInterface::class);
        $this->imageFilterLoader->addCustomImageFilterProvider($customFilterProvider);

        $this->imageFilterLoader->load();
    }

    public function testForceLoad()
    {
        $this->imageTypeProvider->expects(self::exactly(2))
            ->method('getImageDimensions')
            ->willReturn([]);

        $this->imageFilterLoader->load();

        // Try to force load configuration again when nothing has changed
        $this->imageFilterLoader->forceLoad();
    }

    public function testLoadWhenNewCustomImageFilterProviderAdded()
    {
        $this->imageTypeProvider->expects(self::once())
            ->method('getImageDimensions')
            ->willReturn([]);

        $this->imageFilterLoader->load();

        // Try to load configuration again when nothing has changed
        $this->imageFilterLoader->load();
    }

    private function prepareFilterDataForResize(int $width, int $height): array
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

    private function prepareFilterDataForResizeWithAuto(mixed $width, mixed $height): array
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

    private function prepareBaseFilterData(): array
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
