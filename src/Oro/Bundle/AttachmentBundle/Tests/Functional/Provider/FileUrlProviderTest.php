<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tests\Functional\DataFixtures\LoadImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class FileUrlProviderTest extends WebTestCase
{
    private FileUrlProviderInterface $fileUrlProvider;

    protected function setUp(): void
    {
        $this->initClient([], self::generateWsseAuthHeader());

        $this->loadFixtures([LoadImageData::class]);

        $this->fileUrlProvider = self::getContainer()->get('oro_attachment.provider.file_url');
    }

    public function testGetFileUrlWhenExternalUrlIsNotEmpty(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_EXTERNAL);
        $url = $this->fileUrlProvider->getFileUrl($file);

        self::assertEquals('https://example.org/child.file', $url);
    }

    public function testGetFileUrlWhenExternalUrlIsEmpty(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $url = $this->fileUrlProvider->getFileUrl($file);

        self::assertNotEquals('https://example.org/child.file', $url);
        self::assertStringContainsString('/attachment/get/', $url);
    }

    public function testGetResizedImageUrlWhenExternalUrlIsNotEmpty(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_EXTERNAL);
        $url = $this->fileUrlProvider->getResizedImageUrl($file, 10, 10);

        self::assertEquals('https://example.org/child.file', $url);
    }

    public function testGetResizedImageUrlWhenExternalUrlIsEmpty(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $url = $this->fileUrlProvider->getResizedImageUrl($file, 10, 10);

        self::assertNotEquals('https://example.org/child.file', $url);
        self::assertStringContainsString('/attachment/resize/', $url);
    }

    public function testGetFilteredImageUrlWhenExternalUrlIsNotEmpty(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_EXTERNAL);
        $url = $this->fileUrlProvider->getFilteredImageUrl($file, 'original');

        self::assertEquals('https://example.org/child.file', $url);
    }

    public function testGetFilteredImageUrlWhenExternalUrlIsEmpty(): void
    {
        /** @var File $file */
        $file = $this->getReference(LoadImageData::IMAGE_JPG);
        $url = $this->fileUrlProvider->getFilteredImageUrl($file, 'original');

        self::assertNotEquals('https://example.org/child.file', $url);
        self::assertStringContainsString('/attachment/filter/original/', $url);
    }
}
