<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Imagine;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Imagine\ImagineFilterService;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImageProviderInterface;

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
        $this->resizedImageProvider = $this->createMock(ResizedImageProviderInterface::class);

        $this->cacheManager
            ->expects(self::any())
            ->method('resolve')
            ->willReturnCallback(static function (string $path, string $filterName, string $resolver) {
                return '/' . $resolver . '_' . $filterName . '/' . $path;
            });

        $this->imagineFilterService = new ImagineFilterService(
            $this->dataManager,
            $this->cacheManager,
            $this->resizedImageProvider
        );
    }

    /**
     * @dataProvider getUrlOfFilteredImageDataProvider
     */
    public function testGetUrlOfFilteredImageReturnsUrlForAlreadyStored(
        string $path,
        string $targetPath,
        string $format
    ): void {
        $filterName = 'sample_filter';
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
                'format' => '',
            ],
            'uses unchanged target path' => [
                'path' => '/sample/image.png',
                'targetPath' => '/sample/image.png',
                'format' => '',
            ],
            'adds webp extension to target path' => [
                'path' => '/sample/image.png',
                'targetPath' => '/sample/image.png.webp',
                'format' => 'webp',
            ],
            'uses unchanged target path because extension is already webp' => [
                'path' => '/sample/image.png.webp',
                'targetPath' => '/sample/image.png.webp',
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
        string $format
    ): void {
        $filterName = 'sample_filter';
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

        $filteredImageBinary = new Binary('sample_filtered_binary', 'image/sample');
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
