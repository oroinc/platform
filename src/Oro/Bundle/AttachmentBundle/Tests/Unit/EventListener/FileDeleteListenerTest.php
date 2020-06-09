<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\EventListener\FileDeleteListener;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;

class FileDeleteListenerTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var FileDeleteListener */
    private $listener;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var File */
    private $file;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);

        $this->listener = new FileDeleteListener($this->fileManager);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->file = new File();

        $this->setUpLoggerMock($this->listener);
    }

    public function testPostRemoveWhenException(): void
    {
        $this->file->setFilename($filename = 'sample/file');

        $this->fileManager
            ->expects($this->once())
            ->method('deleteFile')
            ->with($filename)
            ->willThrowException(new \Exception());

        $this->assertLoggerWarningMethodCalled();

        $this->listener->postRemove($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }

    public function testPostRemove(): void
    {
        $this->file->setFilename($filename = 'sample/file');

        $this->fileManager
            ->expects($this->once())
            ->method('deleteFile')
            ->with($filename);

        $this->loggerMock
            ->expects($this->never())
            ->method('warning');

        $this->listener->postRemove($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }

    public function testPostUpdateWhenFilenameUnchanged(): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork = $this->createMock(UnitOfWork::class));

        $unitOfWork
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn($changeSet = ['sampleField' => ['sampleValue1', 'sampleValue2']]);

        $this->fileManager
            ->expects($this->never())
            ->method('deleteFile');

        $this->loggerMock
            ->expects($this->never())
            ->method('warning');

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }

    public function testPostUpdateWhenException(): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork = $this->createMock(UnitOfWork::class));

        $unitOfWork
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn($changeSet = ['filename' => [$filename = 'name1', 'name2']]);

        $this->fileManager
            ->expects($this->once())
            ->method('deleteFile')
            ->with($filename)
            ->willThrowException(new \Exception());

        $this->assertLoggerWarningMethodCalled();

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }

    public function testPostUpdate(): void
    {
        $this->entityManager
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork = $this->createMock(UnitOfWork::class));

        $unitOfWork
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn($changeSet = ['filename' => [$filename = 'name1', 'name2']]);

        $this->fileManager
            ->expects($this->once())
            ->method('deleteFile')
            ->with($filename);

        $this->loggerMock
            ->expects($this->never())
            ->method('warning');

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }
}
