<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProvider;

class FileNameProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetFileName(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');

        $provider = new FileNameProvider();
        self::assertSame($file->getFilename(), $provider->getFileName($file));
    }

    public function testGetFileNameReturnsUnchangedWhenSameFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');

        $provider = new FileNameProvider();
        self::assertSame($file->getFilename(), $provider->getFileName($file, 'jpeg'));
    }

    public function testGetFileNameReturnsWithNewExtensionWhenNewFormat(): void
    {
        $file = new File();
        $file->setFilename('filename.jpeg');
        $file->setOriginalFilename('original-filename.jpeg');
        $file->setExtension('jpeg');

        $provider = new FileNameProvider();
        self::assertSame($file->getFilename() . '.webp', $provider->getFileName($file, 'webp'));
    }
}
