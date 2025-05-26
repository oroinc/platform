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

    public function testGetPathForResizedImage(): void
    {
        $entity = new File();
        $width = 10;
        $height = 20;
        $format = 'sample_format';
        $url = 'sample/url';

        $this->fileUrlProvider->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($entity, $width, $height, $format)
            ->willReturn($url);

        self::assertEquals(
            '/' . $url,
            $this->provider->getPathForResizedImage($entity, $width, $height, $format)
        );
    }

    public function testGetPathForFilteredImage(): void
    {
        $entity = new File();
        $filter = 'sample-filter';
        $format = 'sample_format';
        $url = 'sample/url';

        $this->fileUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($entity, $filter, $format)
            ->willReturn($url);

        self::assertEquals(
            '/' . $url,
            $this->provider->getPathForFilteredImage($entity, $filter, $format)
        );
    }
}
