<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Controller;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadFileData;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->loadFixtures([LoadFileData::class, LoadImageData::class]);
    }

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
                    'id' => PHP_INT_MAX,
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
                    'id' => PHP_INT_MAX,
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

    public function testGetFileReturnsFile(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadFileData::FILE_1);
        $url = self::getContainer()->get(FileUrlProviderInterface::class)
            ->getFileUrl($file, FileUrlProviderInterface::FILE_ACTION_GET);
        $this->client->request('GET', $url);
        $result = $this->client->getResponse();
        self::assertResponseContentTypeEquals($result, 'text/plain; charset=UTF-8');
        self::assertResponseHeader($result, 'Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private');
        self::assertResponseHeader($result, 'Content-Length', $file->getFileSize());
        self::assertResponseStatusCodeEquals($result, 200);
    }

    public function testGetResizedAttachmentImageReturns404WhenFileNotExists(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_resize_attachment',
                [
                    'id' => PHP_INT_MAX,
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
                    'id' => PHP_INT_MAX,
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

        self::assertResponseContentTypeEquals($result, 'image/webp');
        self::assertResponseStatusCodeEquals($result, 200);
    }
}
