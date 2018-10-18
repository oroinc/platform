<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Gaufrette\Filesystem;
use Gaufrette\Stream;
use Gaufrette\StreamMode;
use Knp\Bundle\GaufretteBundle\FilesystemMap;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManager;
use Prophecy\Argument;

class MediaCacheManagerTest extends \PHPUnit\Framework\TestCase
{
    const PREFIX = 'media/cache';
    const FILESYSTEM_NAME = 'mediacache';
    const CONTENT = 'content';
    const PATH = 'media/cache/file1.jpg';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var MediaCacheManager
     */
    protected $mediaCacheManager;

    public function setUp()
    {
        $this->filesystem = $this->prophesize(Filesystem::class);

        $filesystemMap = $this->prophesize(FilesystemMap::class);
        $filesystemMap->get(self::FILESYSTEM_NAME)->willReturn($this->filesystem->reveal());

        $this->mediaCacheManager = new MediaCacheManager(self::FILESYSTEM_NAME, self::PREFIX);
        $this->mediaCacheManager->setFilesystemMap($filesystemMap->reveal());
    }

    public function testStore()
    {
        $stream = $this->prophesize(Stream::class);
        $stream->open(Argument::type(StreamMode::class))->shouldBeCalled();
        $stream->write(self::CONTENT)->shouldBeCalled();
        $stream->close()->shouldBeCalled();

        $this->filesystem->createStream('file1.jpg')->willReturn($stream->reveal());
        $this->mediaCacheManager->store(self::CONTENT, self::PATH);
    }

    public function testExists()
    {
        $this->filesystem->has('file1.jpg')->willReturn(true);
        $this->assertTrue($this->mediaCacheManager->exists(self::PATH));
    }
}
