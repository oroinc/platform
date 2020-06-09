<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Oro\Bundle\AttachmentBundle\Acl\FileAccessControlChecker;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManagerRegistry;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;

class MediaCacheManagerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileAccessControlChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $fileAccessControlChecker;

    /** @var GaufretteFileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $publicMediaCacheManager;

    /** @var GaufretteFileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $protectedMediaCacheManager;

    /** @var MediaCacheManagerRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->fileAccessControlChecker = $this->createMock(FileAccessControlChecker::class);
        $this->publicMediaCacheManager = $this->createMock(GaufretteFileManager::class);
        $this->protectedMediaCacheManager = clone $this->publicMediaCacheManager;

        $this->registry = new MediaCacheManagerRegistry(
            $this->fileAccessControlChecker,
            $this->publicMediaCacheManager,
            $this->protectedMediaCacheManager
        );
    }

    public function testGetManagerForFile(): void
    {
        $this->fileAccessControlChecker
            ->method('isCoveredByAcl')
            ->withConsecutive([$file1 = new File()], [$file2 = new File()])
            ->willReturnOnConsecutiveCalls(true, false);

        self::assertSame(
            $this->protectedMediaCacheManager,
            $this->registry->getManagerForFile($file1)
        );
        self::assertSame(
            $this->publicMediaCacheManager,
            $this->registry->getManagerForFile($file2)
        );
    }
}
