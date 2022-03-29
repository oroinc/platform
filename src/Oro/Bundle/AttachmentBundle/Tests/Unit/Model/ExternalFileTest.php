<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Model;

use Oro\Bundle\AttachmentBundle\Model\ExternalFile;

class ExternalFileTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider gettersDataProvider
     */
    public function testGetters(
        string $url,
        string $originalName,
        string $pathname,
        int $size,
        string $mimeType,
        string $filename,
        string $extension
    ): void {
        $externalFile = new ExternalFile($url, $originalName, $size, $mimeType);

        self::assertEquals($url, $externalFile->getUrl());
        self::assertEquals($originalName, $externalFile->getOriginalName());
        self::assertEquals($pathname, $externalFile->getPathname());
        self::assertEquals($size, $externalFile->getSize());
        self::assertEquals($mimeType, $externalFile->getMimeType());
        self::assertEquals($filename, $externalFile->getFilename());
        self::assertEquals($extension, $externalFile->getExtension());
    }

    public function gettersDataProvider(): array
    {
        return [
            [
                'url' => '',
                'originalFilename' => '',
                'pathname' => '',
                'size' => 0,
                'mimeType' => '',
                'filename' => '',
                'extension' => '',
            ],
            [
                'url' => 'http://example.org/image.png',
                'originalFilename' => 'original-image.png',
                'pathname' => '/image.png',
                'size' => 4242,
                'mimeType' => 'image/png',
                'filename' => '/image.png',
                'extension' => 'png',
            ],
            [
                'url' => 'http://example.org/path/image.png?sample_param=sample-value',
                'originalFilename' => 'original-image.png',
                'pathname' => '/path/image.png',
                'size' => 4242,
                'mimeType' => 'image/png',
                'filename' => 'image.png',
                'extension' => 'png',
            ],
        ];
    }

    public function testGetOriginalExtension(): void
    {
        $externalFile = new ExternalFile('http://example.org/image.png', 'original-image.png');

        self::assertEquals('http://example.org/image.png', $externalFile->getUrl());
        self::assertEquals('original-image.png', $externalFile->getOriginalName());
        self::assertEquals('png', $externalFile->getOriginalExtension());
    }

    public function testToString(): void
    {
        $externalFile = new ExternalFile('http://example.org/image.png', 'original-image.png');

        self::assertEquals('http://example.org/image.png', (string) $externalFile);
    }
}
