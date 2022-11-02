<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PictureSourcesProviderTest extends \PHPUnit\Framework\TestCase
{
    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private PictureSourcesProvider $provider;

    protected function setUp(): void
    {
        $this->attachmentManager = $this->createMock(AttachmentManager::class);

        $this->provider = new PictureSourcesProvider($this->attachmentManager);
    }

    public function testGetFilteredPictureSourcesNoFile(): void
    {
        self::assertEquals(
            [
                'src' => null,
                'sources' => [],
            ],
            $this->provider->getFilteredPictureSources(null, 'filter_name')
        );
    }

    public function testGetFilteredPictureSourcesFile(): void
    {
        $image = (new File())
            ->setFilename('image1.jpg');
        $filterName = 'original';
        $filteredImageUrl = '/url/to/image.jpg';

        $this->attachmentManager
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($image, $filterName, '', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($filteredImageUrl);

        self::assertEquals(
            [
                'src' => $filteredImageUrl,
                'sources' => [],
            ],
            $this->provider->getFilteredPictureSources($image, $filterName)
        );
    }

    public function testGetResizedPictureSourcesNoFile(): void
    {
        self::assertEquals(
            [
                'src' => null,
                'sources' => [],
            ],
            $this->provider->getResizedPictureSources(null, 42, 24)
        );
    }

    public function testGetResizedPictureSourcesFile(): void
    {
        $image = (new File())
            ->setFilename('image1.jpg');
        $width = 42;
        $height = 24;
        $filteredImageUrl = '/url/to/image.jpg';

        $this->attachmentManager
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($image, $width, $height, '', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($filteredImageUrl);

        self::assertEquals(
            [
                'src' => $filteredImageUrl,
                'sources' => [],
            ],
            $this->provider->getResizedPictureSources($image, $width, $height)
        );
    }
}
