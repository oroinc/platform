<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\ExternalUrlProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExternalUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    private FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject $innerFileUrlProvider;

    private ExternalUrlProvider $provider;

    protected function setUp(): void
    {
        $this->innerFileUrlProvider = $this->createMock(FileUrlProviderInterface::class);

        $this->provider = new ExternalUrlProvider($this->innerFileUrlProvider);
    }

    public function testGetFileUrlWhenExternalUrlIsEmpty(): void
    {
        $file = $this->getFile();
        $action = 'action';
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $fileUrl = '/path/to/file';

        $this->innerFileUrlProvider->expects(self::once())
            ->method('getFileUrl')
            ->with($file, $action, $referenceType)
            ->willReturn($fileUrl);

        self::assertEquals($fileUrl, $this->provider->getFileUrl($file, $action, $referenceType));
    }

    public function testGetFileUrlWhenExternalUrlIsNotEmpty(): void
    {
        $externalFileUrl = 'https://example.org/filename';
        $file = $this->getFile($externalFileUrl);
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;

        $this->innerFileUrlProvider->expects(self::never())
            ->method('getFileUrl');

        self::assertEquals($externalFileUrl, $this->provider->getFileUrl($file, 'action', $referenceType));
    }

    public function testGetResizedImageUrlWhenExternalUrlIsEmpty(): void
    {
        $file = $this->getFile();
        $format = 'format';
        $width = $height = 10;
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $fileUrl = '/path/to/file';

        $this->innerFileUrlProvider->expects(self::once())
            ->method('getResizedImageUrl')
            ->with($file, $width, $height, $format, $referenceType)
            ->willReturn($fileUrl);

        self::assertEquals(
            $fileUrl,
            $this->provider->getResizedImageUrl($file, $width, $height, $format, $referenceType)
        );
    }

    public function testGetResizedImageUrlWhenExternalUrlIsNotEmpty(): void
    {
        $externalFileUrl = 'https://example.org/filename';
        $file = $this->getFile($externalFileUrl);
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;

        $this->innerFileUrlProvider->expects(self::never())
            ->method('getResizedImageUrl');

        self::assertEquals(
            $externalFileUrl,
            $this->provider->getResizedImageUrl($file, 10, 10, 'format', $referenceType)
        );
    }

    public function testGetFilteredImageUrlWhenExternalUrlIsEmpty(): void
    {
        $file = $this->getFile();
        $filter = 'filter';
        $format = 'format';
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $fileUrl = '/path/to/file';

        $this->innerFileUrlProvider->expects(self::once())
            ->method('getFilteredImageUrl')
            ->with($file, $filter, $format, $referenceType)
            ->willReturn($fileUrl);

        self::assertEquals($fileUrl, $this->provider->getFilteredImageUrl($file, $filter, $format, $referenceType));
    }

    public function testGetFilteredImageUrlWhenExternalUrlIsNotEmpty(): void
    {
        $externalFileUrl = 'https://example.org/filename';
        $file = $this->getFile($externalFileUrl);
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;

        $this->innerFileUrlProvider->expects(self::never())
            ->method('getFilteredImageUrl');

        self::assertEquals(
            $externalFileUrl,
            $this->provider->getFilteredImageUrl($file, 'filter', 'format', $referenceType)
        );
    }

    private function getFile(
        string $externalUrl = null
    ): File|\PHPUnit\Framework\MockObject\MockObject {
        $file = $this->createMock(File::class);
        $file->expects(self::any())
            ->method('getId')
            ->willReturn(1);

        $file->expects(self::any())
            ->method('getFilename')
            ->willReturn('filename');

        $file->expects(self::any())
            ->method('getExternalUrl')
            ->willReturn($externalUrl);

        return $file;
    }
}
