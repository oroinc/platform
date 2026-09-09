<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResizedImagePathProviderTest extends TestCase
{
    private FileUrlProviderInterface&MockObject $fileUrlProvider;
    private ResizedImagePathProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);

        $this->provider = new ResizedImagePathProvider($this->fileUrlProvider);
    }

    /**
     * @dataProvider pathDataProvider
     */
    public function testGetPathForResizedImage(string $url, string $expectedPath): void
    {
        $entity = new File();
        $width = 10;
        $height = 20;
        $format = 'sample_format';

        $this->fileUrlProvider->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($entity, $width, $height, $format)
            ->willReturn($url);

        self::assertEquals(
            $expectedPath,
            $this->provider->getPathForResizedImage($entity, $width, $height, $format)
        );
    }

    /**
     * @dataProvider pathDataProvider
     */
    public function testGetPathForFilteredImage(string $url, string $expectedPath): void
    {
        $entity = new File();
        $filter = 'sample-filter';
        $format = 'sample_format';

        $this->fileUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($entity, $filter, $format)
            ->willReturn($url);

        self::assertEquals(
            $expectedPath,
            $this->provider->getPathForFilteredImage($entity, $filter, $format)
        );
    }

    public function pathDataProvider(): array
    {
        return [
            'relative url' => [
                'url' => 'sample/url',
                'expectedPath' => '/sample/url',
            ],
            'absolute url path' => [
                'url' => '/sample/url',
                'expectedPath' => '/sample/url',
            ],
            'latin accented characters are decoded' => [
                'url' => '/media/cache/attachment/filter/avatar_med/1/caf%C3%A9.jpg',
                'expectedPath' => '/media/cache/attachment/filter/avatar_med/1/café.jpg',
            ],
            'cyrillic characters are decoded' => [
                'url' => '/media/cache/attachment/filter/avatar_med/1/%D1%84%D0%B0%D0%B9%D0%BB.jpg',
                'expectedPath' => '/media/cache/attachment/filter/avatar_med/1/файл.jpg',
            ],
            'encoded space is decoded' => [
                'url' => '/media/cache/attachment/filter/avatar_med/1/my%20photo.jpg',
                'expectedPath' => '/media/cache/attachment/filter/avatar_med/1/my photo.jpg',
            ],
            'plus sign is not a space' => [
                'url' => '/media/cache/attachment/filter/avatar_med/1/a+b.jpg',
                'expectedPath' => '/media/cache/attachment/filter/avatar_med/1/a+b.jpg',
            ],
            'already decoded url is not changed' => [
                'url' => '/media/cache/attachment/filter/avatar_med/1/café.jpg',
                'expectedPath' => '/media/cache/attachment/filter/avatar_med/1/café.jpg',
            ],
        ];
    }
}
