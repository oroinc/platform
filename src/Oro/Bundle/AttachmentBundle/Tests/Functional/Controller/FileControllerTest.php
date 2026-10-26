<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Controller;

use Oro\Bundle\AttachmentBundle\Controller\FileController;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManagerRegistryInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadFileData;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadFileMimeTypeData;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([LoadFileData::class, LoadImageData::class, LoadFileMimeTypeData::class]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->client->enableReboot();
    }

    public function testGetFileReturns404WhenFileNotExists(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_attachment_get_file',
                [
                    'id' => self::BIGINT,
                    'action' => FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
                    'filename' => 'sample-filename',
                ]
            )
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testGetFileReturns404WhenFileExistsButFilenameIsInvalid(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_attachment_get_file',
                [
                    'id' => self::BIGINT,
                    'action' => FileUrlProviderInterface::FILE_ACTION_DOWNLOAD,
                    'filename' => 'invalid-filename',
                ]
            )
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testGetFileDownloadsFile(): void
    {
        $file = $this->getReference(LoadFileData::FILE_1);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertResponseContentTypeEquals($result, 'application/force-download');
        self::assertResponseHeader($result, 'Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        self::assertResponseHeader($result, 'Content-Disposition', 'attachment');
        self::assertResponseHeader($result, 'Content-Security-Policy', 'sandbox');
        self::assertResponseHeader($result, 'Content-Length', $file->getFileSize());
        self::assertResponseStatusCodeEquals($result, 200);
    }

    public function testGetFileDownloadsFileWithOriginalFileName(): void
    {
        $file = $this->getReference(LoadFileData::FILE_2);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_DOWNLOAD);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertResponseContentTypeEquals($result, 'application/force-download');
        self::assertResponseHeader($result, 'Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        self::assertResponseHeader(
            $result,
            'Content-Disposition',
            sprintf('attachment;filename="%s"', addslashes($file->getOriginalFilename()))
        );
        self::assertResponseHeader($result, 'Content-Length', $file->getFileSize());
        self::assertResponseStatusCodeEquals($result, 200);
    }

    /**
     * @dataProvider getFileServesInlineTypeDataProvider
     */
    public function testGetFileServesInlineType(string $fileReference, string $expectedContentType): void
    {
        /** @var File $file */
        $file = $this->getReference($fileReference);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_GET);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($result, 200);
        self::assertResponseContentTypeEquals($result, $expectedContentType);
        self::assertResponseHeader($result, 'Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        self::assertResponseHeader($result, 'Content-Length', $file->getFileSize());
        self::assertResponseHeaderNotExists($result, 'Content-Disposition');
        self::assertResponseHeaderNotExists($result, 'Content-Security-Policy');
    }

    public function getFileServesInlineTypeDataProvider(): array
    {
        return [
            'text stays inline' => [
                'fileReference' => LoadFileData::FILE_1,
                'expectedContentType' => 'text/plain; charset=utf-8',
            ],
            'uppercase type with charset parameter is reduced to the media type' => [
                'fileReference' => LoadFileMimeTypeData::FILE_TEXT_UPPERCASE_WITH_CHARSET,
                'expectedContentType' => 'text/plain; charset=utf-8',
            ],
        ];
    }

    /**
     * A file whose type is outside the inline list keeps its media type but is served as a download
     * under the sandbox policy, so an opened URL cannot execute it in the application origin.
     * A file without a valid type is served as a binary download.
     *
     * @dataProvider getFileForcesDownloadForNonInlineTypeDataProvider
     */
    public function testGetFileForcesDownloadForNonInlineType(
        string $fileReference,
        string $expectedContentType,
        string $expectedContentDisposition
    ): void {
        /** @var File $file */
        $file = $this->getReference($fileReference);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_GET);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($result, 200);
        self::assertResponseContentTypeEquals($result, $expectedContentType);
        self::assertResponseHeader($result, 'Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        self::assertResponseHeader($result, 'Content-Length', $file->getFileSize());
        self::assertResponseHeader($result, 'Content-Disposition', $expectedContentDisposition);
        self::assertResponseHeader($result, 'Content-Security-Policy', 'sandbox');
    }

    public function getFileForcesDownloadForNonInlineTypeDataProvider(): array
    {
        return [
            'html' => [
                'fileReference' => LoadFileMimeTypeData::FILE_HTML,
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedContentDisposition' => 'attachment;filename="file_mime_type_html.txt"',
            ],
            'svg' => [
                'fileReference' => LoadFileMimeTypeData::FILE_SVG,
                'expectedContentType' => 'image/svg+xml',
                'expectedContentDisposition' => 'attachment;filename="file_mime_type_svg.txt"',
            ],
            'control characters in the filename are stripped' => [
                'fileReference' => LoadFileMimeTypeData::FILE_FILENAME_WITH_CONTROL_CHARS,
                'expectedContentType' => 'text/html; charset=utf-8',
                'expectedContentDisposition' => 'attachment;filename="probe.html"',
            ],
        ];
    }

    public function testGetResizedAttachmentImageReturns404WhenFileNotExists(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_resize_attachment',
                [
                    'id' => self::BIGINT,
                    'width' => 42,
                    'height' => 142,
                    'filename' => 'sample-filename',
                ]
            )
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testGetResizedAttachmentImageReturns404WhenFileExistsButFilenameIsInvalid(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_resize_attachment',
                [
                    'id' => $file->getId(),
                    'width' => 42,
                    'height' => 142,
                    'filename' => 'sample-filename',
                ]
            )
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testGetResizedAttachmentImage(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getResizedImageUrl($file, 42, 142, '');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertResponseContentTypeEquals($result, $file->getMimeType());
        self::assertResponseStatusCodeEquals($result, 200);
        self::assertResponseHeaderNotExists($result, AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER);
        self::assertResponseHeader(
            $result,
            'Cache-Control',
            sprintf('max-age=%d, public', FileController::CACHE_CONTROL_DEFAULT_MAX_AGE)
        );
    }

    public function testGetResizedAttachmentImageReturnsWebpImageWhenFormatIsWebp(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getResizedImageUrl($file, 42, 142, 'webp');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertResponseContentTypeEquals($result, 'image/webp');
        self::assertResponseStatusCodeEquals($result, 200);
    }

    public function testGetFilteredImageReturns404WhenFileNotExists(): void
    {
        self::getContainer()->get('liip_imagine.filter.configuration')
            ->set(__FUNCTION__, []);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_filtered_attachment',
                [
                    'id' => self::BIGINT,
                    'filter' => __FUNCTION__,
                    'filterMd5' => md5(__FUNCTION__),
                    'filename' => 'sample-filename',
                ]
            )
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testGetFilteredImageReturns404WhenFileExistsButFilenameIsInvalid(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_filtered_attachment',
                [
                    'id' => $file->getId(),
                    'filter' => __FUNCTION__,
                    'filterMd5' => md5(__FUNCTION__),
                    'filename' => 'sample-filename',
                ]
            )
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testGetFilteredImageReturns404WhenFilterDoesNotExist(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_filtered_attachment',
                [
                    'id' => $file->getId(),
                    'filter' => 'invalid_filter',
                    'filterMd5' => md5(__FUNCTION__),
                    'filename' => $file->getFilename(),
                ]
            )
        );
        $result = $this->client->getResponse();

        self::assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testGetFilteredImage(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFilteredImageUrl($file, 'original', '');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertEquals(0, $result->headers->getCacheControlDirective('max-age'));
        self::assertResponseContentTypeEquals($result, $file->getMimeType());
        self::assertResponseStatusCodeEquals($result, 200);
    }

    public function testGetFilteredImageReturnsWebpImageWhenFormatIsWebp(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFilteredImageUrl($file, 'original', 'webp');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertEquals(0, $result->headers->getCacheControlDirective('max-age'));
        self::assertResponseContentTypeEquals($result, 'image/webp');
        self::assertResponseStatusCodeEquals($result, 200);
    }

    public function testGetFilteredImageReturnsPngImageWhenFilterConfigHasFormatPng(): void
    {
        // Prevents kernel from shutting down before request is made to keep the added LiipImagine filter.
        $this->client->disableReboot();

        $filterName = md5(uniqid(__METHOD__, true));
        self::getContainer()->get('liip_imagine.filter.configuration')
            ->set($filterName, ['format' => 'png']);

        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFilteredImageUrl($file, $filterName);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertEquals(0, $result->headers->getCacheControlDirective('max-age'));
        self::assertResponseContentTypeEquals($result, 'image/png');
        self::assertResponseStatusCodeEquals($result, 200);
    }

    public function testGetFilteredImageReturnsWebpImageWhenFilterConfigHasFormatPng(): void
    {
        // Prevents kernel from shutting down before request is made to keep the added LiipImagine filter.
        $this->client->disableReboot();

        $filterName = md5(uniqid(__METHOD__, true));
        self::getContainer()->get('liip_imagine.filter.configuration')
            ->set($filterName, ['format' => 'png']);

        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFilteredImageUrl($file, $filterName, 'webp');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertEquals(0, $result->headers->getCacheControlDirective('max-age'));
        self::assertResponseContentTypeEquals($result, 'image/webp');
        self::assertResponseStatusCodeEquals($result, 200);
    }

    public function testGetFilteredAvatarImage(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFilteredImageUrl($file, 'avatar_med', '');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertTrue($result->headers->getCacheControlDirective('public'));
        self::assertNotEquals(0, $result->headers->getCacheControlDirective('max-age'));
        self::assertResponseContentTypeEquals($result, $file->getMimeType());
        self::assertResponseStatusCodeEquals($result, 200);
    }

    public function testGetResizedAttachmentImageForSvgIsSandboxed(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_SVG);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getResizedImageUrl($file, 42, 142, '');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($result, 200);
        self::assertResponseContentTypeEquals($result, 'image/svg+xml');
        self::assertResponseHeader($result, 'Content-Security-Policy', 'sandbox');
    }

    public function testGetFilteredImageForSvgIsSandboxed(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_SVG);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFilteredImageUrl($file, 'original', '');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($result, 200);
        self::assertResponseContentTypeEquals($result, 'image/svg+xml');
        self::assertResponseHeader($result, 'Content-Security-Policy', 'sandbox');
    }

    public function testGetResizedAttachmentImageStoresImageUnderDecodedPath(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG_NON_ASCII_NAME);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getResizedImageUrl($file, 42, 142, '');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertResponseContentTypeEquals($result, $file->getMimeType());
        self::assertResponseStatusCodeEquals($result, 200);
        $this->assertImageStoredUnderDecodedPath($file, $url);
    }

    public function testGetFilteredImageStoresImageUnderDecodedPath(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG_NON_ASCII_NAME);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFilteredImageUrl($file, 'avatar_med', '');
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();

        self::assertResponseContentTypeEquals($result, $file->getMimeType());
        self::assertResponseStatusCodeEquals($result, 200);
        $this->assertImageStoredUnderDecodedPath($file, $url);
    }

    private function assertImageStoredUnderDecodedPath(File $file, string $url): void
    {
        $encodedPath = preg_replace('~^.*?/media/cache/~', '', $url);
        $decodedPath = rawurldecode($encodedPath);
        self::assertNotEquals($encodedPath, $decodedPath, 'The URL is expected to contain encoded characters.');
        self::assertStringContainsString('фото-кафе.jpg', $decodedPath);

        /** @var MediaCacheManagerRegistryInterface $registry */
        $registry = self::getContainer()->get('oro_attachment.tests.media_cache_manager_registry');
        $mediaCacheManager = $registry->getManagerForFile($file);

        self::assertTrue(
            $mediaCacheManager->hasFile($decodedPath),
            sprintf('The image is expected to be stored under the decoded path "%s".', $decodedPath)
        );
        self::assertFalse(
            $mediaCacheManager->hasFile($encodedPath),
            sprintf('The image is not expected to be stored under the encoded path "%s".', $encodedPath)
        );
    }
}
