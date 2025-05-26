<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileIconProvider;
use PHPUnit\Framework\TestCase;

class FileIconProviderTest extends TestCase
{
    private const string JPEG_ICON = 'jpeg-icon';
    private const string DEFAULT_ICON = 'default-icon';
    private const string JPEG_EXT = 'jpeg';
    private const string DEFAULT_EXT = 'default';
    private const array FILE_ICONS = [self::JPEG_EXT => self::JPEG_ICON, self::DEFAULT_EXT => self::DEFAULT_ICON];

    private FileIconProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new FileIconProvider(self::FILE_ICONS);
    }

    public function testGetAttachmentIconClass(): void
    {
        $file = new File();
        $file->setExtension(self::JPEG_EXT);

        self::assertSame(self::JPEG_ICON, $this->provider->getExtensionIconClass($file));

        $file->setExtension('not-existing');
        self::assertSame(self::DEFAULT_ICON, $this->provider->getExtensionIconClass($file));
    }

    public function testGetFileIcons(): void
    {
        self::assertEquals(self::FILE_ICONS, $this->provider->getFileIcons());
    }
}
