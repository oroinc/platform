<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Imagine;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Imagine\ImagineFilterService;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;

class ImagineFilterServiceTest extends \PHPUnit\Framework\TestCase
{
    private DataManager|\PHPUnit\Framework\MockObject\MockObject $dataManager;

    private CacheManager|\PHPUnit\Framework\MockObject\MockObject $cacheManager;

    private ResizedImageProviderInterface|\PHPUnit\Framework\MockObject\MockObject $resizedImageProvider;

    private ImagineFilterService $imagineFilterService;

    protected function setUp(): void
    {
        $this->dataManager = $this->createMock(DataManager::class);
        $this->cacheManager = $this->createMock(CacheManager::class);
        $filterConfiguration = $this->createMock(FilterConfiguration::class);
        $this->resizedImageProvider = $this->createMock(ResizedImageProviderInterface::class);
        $filenameExtensionHelper = new FilenameExtensionHelper(['image/svg']);

        $filterConfiguration
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([['jpeg_filter', ['format' => 'jpeg']], ['empty_filter', []]]);

        $this->cacheManager
            ->expects(self::any())
            ->method('resolve')
            ->willReturnCallback(static function (string $path, string $filterName, string $resolver) {
                return '/' . $resolver . '_' . $filterName . '/' . $path;
            });

        $this->imagineFilterService = new ImagineFilterService(
            $this->dataManager,
            $this->cacheManager,
            $filterConfiguration,
            $this->resizedImageProvider,
            $filenameExtensionHelper
        );
    }

    /**
     * @dataProvider getUrlOfFilteredImageDataProvider
     */
    public function testGetUrlOfFilteredImageReturnsUrlForAlreadyStored(
        string $path,
        string $targetPath,
        string $filterName,
        string $format
    ): void {
        $resolver = 'sample_resolver';
        $this->cacheManager
            ->expects(self::once())
            ->method('isStored')
            ->with($targetPath, $filterName, $resolver)
            ->willReturn(true);

        $this->resizedImageProvider
            ->expects(self::never())
            ->method(self::anything());

        $this->cacheManager
            ->expects(self::never())
            ->method('store');

        self::assertEquals(
            '/' . $resolver . '_' . $filterName . '/' . $targetPath,
            $this->imagineFilterService->getUrlOfFilteredImage($path, $filterName, $format, $resolver)
        );
    }

    public function getUrlOfFilteredImageDataProvider(): array
    {
        return [
            'when path is empty' => [
                'path' => '',
                'targetPath' => '',
                'filter' => 'empty_filter',
                'format' => '',
            ],
            'uses unchanged target path' => [
                'path' => '/sample/image.png',
                'targetPath' => '/sample/image.png',
                'filter' => 'empty_filter',
                'format' => '',
            ],
            'adds webp extension to target path' => [
                'path' => '/sample/image.png',
                'targetPath' => '/sample/image.png.webp',
                'filter' => 'empty_filter',
                'format' => 'webp',
            ],
            'uses unchanged target path because extension is already webp' => [
                'path' => '/sample/image.png.webp',
                'targetPath' => '/sample/image.png.webp',
                'filter' => 'empty_filter',
                'format' => 'webp',
            ],
            'adds jpeg extension as filter config has format jpeg' => [
                'path' => '/sample/image.png',
                'targetPath' => '/sample/image.png.jpeg',
                'filter' => 'jpeg_filter',
                'format' => 'jpeg',
            ],
            'uses unchanged target path because extension is already jpeg for filter config with format jpeg' => [
                'path' => '/sample/image.jpeg',
                'targetPath' => '/sample/image.jpeg',
                'filter' => 'jpeg_filter',
                'format' => 'jpeg',
            ],
            'adds webp extension even if filter config has format jpeg' => [
                'path' => '/sample/image.png',
                'targetPath' => '/sample/image.png.webp',
                'filter' => 'jpeg_filter',
                'format' => 'webp',
            ],
            'extension is not added for unsupported mime type' => [
                'path' => '/sample/image.svg',
                'targetPath' => '/sample/image.svg',
                'filter' => 'webp_filter',
                'format' => 'webp',
            ],
        ];
    }

    /**
     * @dataProvider getUrlOfFilteredImageDataProvider
     */
    public function testGetUrlOfFilteredImageStoresAndReturnsUrlWhenNotYetStored(
        string $path,
        string $targetPath,
        string $filterName,
        string $format
    ): void {
        $resolver = 'sample_resolver';
        $this->cacheManager
            ->expects(self::once())
            ->method('isStored')
            ->with($targetPath, $filterName, $resolver)
            ->willReturn(false);

        $binary = new Binary('sample_binary', 'image/sample');
        $this->dataManager
            ->expects(self::once())
            ->method('find')
            ->with($filterName, $path)
            ->willReturn($binary);

        $filteredImageBinary = new Binary('empty_filtered_binary', 'image/sample');
        $this->resizedImageProvider
            ->expects(self::once())
            ->method('getFilteredImageByContent')
            ->with($binary->getContent(), $filterName, $format)
            ->willReturn($filteredImageBinary);

        $this->cacheManager
            ->expects(self::once())
            ->method('store')
            ->with($filteredImageBinary, $targetPath, $filterName, $resolver);

        self::assertEquals(
            '/' . $resolver . '_' . $filterName . '/' . $targetPath,
            $this->imagineFilterService->getUrlOfFilteredImage($path, $filterName, $format, $resolver)
        );
    }
}
