<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\WebpAwarePictureSourcesProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WebpAwarePictureSourcesProviderTest extends \PHPUnit\Framework\TestCase
{
    private PictureSourcesProviderInterface|\PHPUnit\Framework\MockObject\MockObject $innerProvider;

    private AttachmentManager|\PHPUnit\Framework\MockObject\MockObject $attachmentManager;

    private WebpAwarePictureSourcesProvider $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(PictureSourcesProviderInterface::class);
        $this->attachmentManager = $this->createMock(AttachmentManager::class);

        $this->provider = new WebpAwarePictureSourcesProvider(
            $this->innerProvider,
            $this->attachmentManager,
            ['image/svg']
        );
    }

    public function testGetFilteredPictureSourcesNoFile(): void
    {
        $this->innerProvider
            ->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with(null, 'filter_name')
            ->willReturn([
                'src' => null,
                'sources' => [],
            ]);

        self::assertEquals(
            [
                'src' => null,
                'sources' => [],
            ],
            $this->provider->getFilteredPictureSources(null, 'filter_name')
        );
    }

    public function testGetFilteredPictureSourcesWhenStoredExternally(): void
    {
        $image = (new File())
            ->setExternalUrl('http://example.org/image.png');
        $filterName = 'original';

        $this->innerProvider
            ->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($image, $filterName)
            ->willReturn([
                'src' => $image->getExternalUrl(),
                'sources' => [],
            ]);

        self::assertEquals(
            [
                'src' => $image->getExternalUrl(),
                'sources' => [],
            ],
            $this->provider->getFilteredPictureSources($image, $filterName)
        );
    }

    public function testGetFilteredPictureSourcesWebpNotEnabledIfSupported(): void
    {
        $image = (new File())
            ->setFilename('image.jpg');
        $filterName = 'original';
        $filteredImageUrl = '/url/to/image.jpg';

        $this->innerProvider
            ->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($image, $filterName)
            ->willReturn([
                'src' => $filteredImageUrl,
                'sources' => [],
            ]);

        $this->attachmentManager
            ->expects(self::never())
            ->method('getFilteredImageUrl');
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(false);

        self::assertEquals(
            [
                'src' => $filteredImageUrl,
                'sources' => [],
            ],
            $this->provider->getFilteredPictureSources($image, $filterName)
        );
    }

    public function testGetFilteredPictureSourcesWebpImage(): void
    {
        $image = (new File())
            ->setFilename('image.webp')
            ->setExtension('webp');
        $filterName = 'original';
        $filteredImageUrl = '/url/to/image.jpg';

        $this->innerProvider
            ->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($image, $filterName)
            ->willReturn([
                'src' => $filteredImageUrl,
                'sources' => [],
            ]);

        $this->attachmentManager
            ->expects(self::never())
            ->method('getFilteredImageUrl');
        $this->attachmentManager
            ->expects(self::never())
            ->method('isWebpEnabledIfSupported');

        self::assertEquals(
            [
                'src' => $filteredImageUrl,
                'sources' => [],
            ],
            $this->provider->getFilteredPictureSources($image, $filterName)
        );
    }

    public function testGetFilteredPictureSourcesWebpEnabledIfSupported(): void
    {
        $image = (new File())
            ->setFilename('image.jpg');
        $filterName = 'original';
        $filteredImageUrl = '/url/to/image.jpg';
        $webpFilteredImageUrl = '/url/to/image.jpg.webp';

        $this->innerProvider
            ->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($image, $filterName)
            ->willReturn([
                'src' => $filteredImageUrl,
                'sources' => [],
            ]);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);
        $this->attachmentManager
            ->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($image, $filterName, 'webp', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($webpFilteredImageUrl);

        self::assertEquals(
            [
                'src' => $filteredImageUrl,
                'sources' => [
                    [
                        'srcset' => $webpFilteredImageUrl,
                        'type' => 'image/webp',
                    ],
                ],
            ],
            $this->provider->getFilteredPictureSources($image, $filterName)
        );
    }

    public function testGetFilteredPictureSourcesUnsupportedMimeType(): void
    {
        $image = (new File())
            ->setFilename('image.svg')
            ->setMimeType('image/svg');
        $filterName = 'original';
        $filteredImageUrl = '/url/to/image.svg';

        $this->innerProvider
            ->expects(self::once())
            ->method('getFilteredPictureSources')
            ->with($image, $filterName)
            ->willReturn([
                'src' => $filteredImageUrl,
                'sources' => [],
            ]);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);
        $this->attachmentManager
            ->expects(self::never())
            ->method('getFilteredImageUrl')
            ->with(self::anything());

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
        $width = 42;
        $height = 24;
        $this->innerProvider
            ->expects(self::once())
            ->method('getResizedPictureSources')
            ->with(null, $width, $height)
            ->willReturn([
                'src' => null,
                'sources' => [],
            ]);

        self::assertEquals(
            [
                'src' => null,
                'sources' => [],
            ],
            $this->provider->getResizedPictureSources(null, $width, $height)
        );
    }

    public function testGetResizedPictureSourcesWhenStoredExternally(): void
    {
        $image = (new File())
            ->setExternalUrl('http://example.org/image.png');
        $width = 42;
        $height = 24;

        $this->innerProvider
            ->expects(self::once())
            ->method('getResizedPictureSources')
            ->with($image, $width, $height)
            ->willReturn([
                'src' => $image->getExternalUrl(),
                'sources' => [],
            ]);

        self::assertEquals(
            [
                'src' => $image->getExternalUrl(),
                'sources' => [],
            ],
            $this->provider->getResizedPictureSources($image, $width, $height)
        );
    }

    public function testGetResizedPictureSourcesWebpNotEnabledIfSupported(): void
    {
        $image = (new File())
            ->setFilename('image.jpg');
        $width = 42;
        $height = 24;
        $resizedImageUrl = '/42/24/image.jpg';

        $this->innerProvider
            ->expects(self::once())
            ->method('getResizedPictureSources')
            ->with($image, $width, $height)
            ->willReturn([
                'src' => $resizedImageUrl,
                'sources' => [],
            ]);

        $this->attachmentManager
            ->expects(self::never())
            ->method('getResizedImageUrl');
        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(false);

        self::assertEquals(
            [
                'src' => $resizedImageUrl,
                'sources' => [],
            ],
            $this->provider->getResizedPictureSources($image, $width, $height)
        );
    }

    public function testGetResizedPictureSourcesWebpImage(): void
    {
        $image = (new File())
            ->setFilename('image.webp')
            ->setExtension('webp');
        $width = 42;
        $height = 24;
        $resizedImageUrl = '/42/24/image.jpg';

        $this->innerProvider
            ->expects(self::once())
            ->method('getResizedPictureSources')
            ->with($image, $width, $height)
            ->willReturn([
                'src' => $resizedImageUrl,
                'sources' => [],
            ]);

        $this->attachmentManager
            ->expects(self::never())
            ->method('getResizedImageUrl');
        $this->attachmentManager
            ->expects(self::never())
            ->method('isWebpEnabledIfSupported');

        self::assertEquals(
            [
                'src' => $resizedImageUrl,
                'sources' => [],
            ],
            $this->provider->getResizedPictureSources($image, $width, $height)
        );
    }

    public function testGetResizedPictureSourcesWebpEnabledIfSupported(): void
    {
        $image = (new File())
            ->setFilename('image.jpg');
        $width = 42;
        $height = 24;
        $resizedImageUrl = '/42/24/image.jpg';
        $webpResizedImageUrl = '/url/to/image.webp';

        $this->innerProvider
            ->expects(self::once())
            ->method('getResizedPictureSources')
            ->with($image, $width, $height)
            ->willReturn([
                'src' => $resizedImageUrl,
                'sources' => [],
            ]);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);
        $this->attachmentManager
            ->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($image, $width, $height, 'webp', UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn($webpResizedImageUrl);

        self::assertEquals(
            [
                'src' => $resizedImageUrl,
                'sources' => [
                    [
                        'srcset' => $webpResizedImageUrl,
                        'type' => 'image/webp',
                    ],
                ],
            ],
            $this->provider->getResizedPictureSources($image, $width, $height)
        );
    }

    public function testGetResizedPictureSourcesUnsupportedMimeType(): void
    {
        $image = (new File())
            ->setFilename('image.jpg')
            ->setMimeType('image/svg');
        $width = 42;
        $height = 24;
        $resizedImageUrl = '/42/24/image.svg';

        $this->innerProvider
            ->expects(self::once())
            ->method('getResizedPictureSources')
            ->with($image, $width, $height)
            ->willReturn([
                'src' => $resizedImageUrl,
                'sources' => [],
            ]);

        $this->attachmentManager
            ->expects(self::once())
            ->method('isWebpEnabledIfSupported')
            ->willReturn(true);
        $this->attachmentManager
            ->expects(self::never())
            ->method('getResizedImageUrl')
            ->with(self::anything());

        self::assertEquals(
            [
                'src' => $resizedImageUrl,
                'sources' => [],
            ],
            $this->provider->getResizedPictureSources($image, $width, $height)
        );
    }
}
