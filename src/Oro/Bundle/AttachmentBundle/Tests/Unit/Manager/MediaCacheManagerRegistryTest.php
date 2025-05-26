<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Manager;

use Oro\Bundle\AttachmentBundle\Acl\FileAccessControlChecker;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\MediaCacheManagerRegistry;
use Oro\Bundle\GaufretteBundle\FileManager as GaufretteFileManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MediaCacheManagerRegistryTest extends TestCase
{
    private FileAccessControlChecker&MockObject $fileAccessControlChecker;
    private GaufretteFileManager&MockObject $publicMediaCacheManager;
    private GaufretteFileManager&MockObject $protectedMediaCacheManager;
    private MediaCacheManagerRegistry $registry;

    #[\Override]
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
        $this->fileAccessControlChecker->expects(self::atLeastOnce())
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
