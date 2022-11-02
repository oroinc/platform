<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileIconProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileTitleProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\MimeTypeChecker;
use Oro\Bundle\DigitalAssetBundle\Provider\PreviewMetadataProvider;

class PreviewMetadataProviderTest extends \PHPUnit\Framework\TestCase
{
    private const ORIGINAL_FILE_NAME = 'sample/original/file.name';
    private const ICON = 'sample-icon';
    private const DOWNLOAD_URL = 'sample/download/url';
    private const MIME_TYPE_1 = 'sample/mime-type1';
    private const PREVIEW_URL = 'sample/preview/url';
    private const SAMPLE_TITLE = 'Sample Title';

    /** @var FileUrlProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileUrlProvider;

    /** @var MimeTypeChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $mimeTypeChecker;

    /** @var FileIconProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $fileIconProvider;

    /** @var FileTitleProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $fileTitleProvider;

    /** @var PreviewMetadataProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->fileUrlProvider = $this->createMock(FileUrlProviderInterface::class);
        $this->mimeTypeChecker = $this->createMock(MimeTypeChecker::class);
        $this->fileIconProvider = $this->createMock(FileIconProvider::class);
        $this->fileTitleProvider = $this->createMock(FileTitleProviderInterface::class);

        $this->provider = new PreviewMetadataProvider(
            $this->fileUrlProvider,
            $this->mimeTypeChecker,
            $this->fileIconProvider,
            $this->fileTitleProvider
        );
    }

    public function testGetMetadataWhenNotImage(): void
    {
        $file = (new File())
            ->setOriginalFilename(self::ORIGINAL_FILE_NAME)
            ->setMimeType(self::MIME_TYPE_1);

        $this->mimeTypeChecker
            ->expects($this->once())
            ->method('isImageMimeType')
            ->with(self::MIME_TYPE_1)
            ->willReturn(false);

        $this->fileIconProvider
            ->expects($this->once())
            ->method('getExtensionIconClass')
            ->with($file)
            ->willReturn(self::ICON);

        $this->fileUrlProvider
            ->expects($this->once())
            ->method('getFileUrl')
            ->with($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD)
            ->willReturn(self::DOWNLOAD_URL);

        $this->fileTitleProvider
            ->expects($this->once())
            ->method('getTitle')
            ->with($file)
            ->willReturn(self::SAMPLE_TITLE);

        $this->assertEquals(
            [
                'filename' => self::ORIGINAL_FILE_NAME,
                'title' => self::SAMPLE_TITLE,
                'preview' => '',
                'icon' => self::ICON,
                'download' => self::DOWNLOAD_URL,
            ],
            $this->provider->getMetadata($file)
        );
    }

    public function testGetMetadataWhenImage(): void
    {
        $file = (new File())
            ->setOriginalFilename(self::ORIGINAL_FILE_NAME)
            ->setMimeType(self::MIME_TYPE_1);

        $this->mimeTypeChecker
            ->expects($this->once())
            ->method('isImageMimeType')
            ->with(self::MIME_TYPE_1)
            ->willReturn(true);

        $this->fileUrlProvider
            ->expects($this->once())
            ->method('getFilteredImageUrl')
            ->with($file, 'digital_asset_icon')
            ->willReturn(self::PREVIEW_URL);

        $this->fileIconProvider
            ->expects($this->once())
            ->method('getExtensionIconClass')
            ->with($file)
            ->willReturn(self::ICON);

        $this->fileUrlProvider
            ->expects($this->once())
            ->method('getFileUrl')
            ->with($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD)
            ->willReturn(self::DOWNLOAD_URL);

        $this->fileTitleProvider
            ->expects($this->once())
            ->method('getTitle')
            ->with($file)
            ->willReturn(self::SAMPLE_TITLE);

        $this->assertEquals(
            [
                'filename' => self::ORIGINAL_FILE_NAME,
                'title' => self::SAMPLE_TITLE,
                'preview' => self::PREVIEW_URL,
                'icon' => self::ICON,
                'download' => self::DOWNLOAD_URL,
            ],
            $this->provider->getMetadata($file)
        );
    }
}
