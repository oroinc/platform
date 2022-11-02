<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Functional\Imagine;

use Gaufrette\Filesystem;
use Gaufrette\StreamWrapper;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Oro\Bundle\GaufretteBundle\Adapter\LocalAdapter;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\TempDirExtension;

class ImagineTest extends WebTestCase
{
    use TempDirExtension;

    private const TEST_FILE_SYSTEM_NAME  = 'testFileSystem';

    /** @var string  */
    private $directory;

    /** @var ImagineInterface */
    private $imagine;

    protected function setUp(): void
    {
        $this->imagine = self::getContainer()->get('oro_attachment.liip_imagine');
        $this->directory = $this->getTempDir('Files');
        $filesystem = new Filesystem(new LocalAdapter($this->directory));
        StreamWrapper::getFilesystemMap()->set(self::TEST_FILE_SYSTEM_NAME, $filesystem);
        StreamWrapper::register();
    }

    public function testLoadFromGaufretteUrl(): void
    {
        $path = $this->directory . '/image.jpg';
        imagejpeg(imagecreatetruecolor(10, 10), $path);
        $image = $this->imagine->open(sprintf('gaufrette://%s/%s', self::TEST_FILE_SYSTEM_NAME, 'image.jpg'));
        $this->assertInstanceOf(ImageInterface::class, $image);
    }
}
