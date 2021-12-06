<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\ResizedImagePathProvider;

class ResizedImagePathProviderTest extends \PHPUnit\Framework\TestCase
{
    private FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject $fileUrlProvider;

    private ResizedImagePathProvider $provider;

    protected function setUp(): void
    {
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);

        $this->provider = new ResizedImagePathProvider($this->fileUrlProvider);
    }

    public function testGetPathForResizedImage(): void
    {
        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($entity = new File(), $width = 10, $height = 20, $format = 'sample_format')
            ->willReturn('sample/url');

        self::assertEquals(
            '/sample/url',
            $this->provider->getPathForResizedImage($entity, $width, $height, $format)
        );
    }

    public function testGetPathForFilteredImage(): void
    {
        $this->fileUrlProvider
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($entity = new File(), $filter = 'sample-filter', $format = 'sample_format')
            ->willReturn($url = 'sample/url');

        self::assertEquals(
            '/sample/url',
            $this->provider->getPathForFilteredImage($entity, $filter, $format)
        );
    }
}
