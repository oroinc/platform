<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileIconProvider;

class FileIconProviderTest extends \PHPUnit\Framework\TestCase
{
    private const JPEG_ICON = 'jpeg-icon';
    private const DEFAULT_ICON = 'default-icon';
    private const JPEG_EXT = 'jpeg';
    private const DEFAULT_EXT = 'default';
    private const FILE_ICONS = [self::JPEG_EXT => self::JPEG_ICON, self::DEFAULT_EXT => self::DEFAULT_ICON];

    /** @var FileIconProvider */
    private $provider;

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
