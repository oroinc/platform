<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\EventListener\FileDeleteListener;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Psr\Log\LoggerInterface;

class FileDeleteListenerTest extends \PHPUnit\Framework\TestCase
{
    private FileManager|\PHPUnit\Framework\MockObject\MockObject $fileManager;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    private File $file;

    private FileDeleteListener $listener;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->file = new File();

        $this->listener = new FileDeleteListener($this->fileManager, $this->logger);
    }

    public function testPostRemoveWhenStoredExternally(): void
    {
        $this->file->setExternalUrl('http://example.org/image.png');

        $this->fileManager->expects(self::never())
            ->method(self::anything());

        $this->listener->postRemove($this->file);
    }

    public function testPostRemoveWhenException(): void
    {
        $this->file->setFilename($filename = 'sample/file');

        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with($filename)
            ->willThrowException(new \Exception());

        $this->logger->expects(self::once())
            ->method('warning');

        $this->listener->postRemove($this->file);
    }

    public function testPostRemove(): void
    {
        $this->file->setFilename($filename = 'sample/file');

        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with($filename);

        $this->logger->expects(self::never())
            ->method('warning');

        $this->listener->postRemove($this->file);
    }

    public function testPostUpdateWhenStoredExternally(): void
    {
        $this->file->setExternalUrl('http://example.org/image.png');

        $this->fileManager->expects(self::never())
            ->method(self::anything());

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }

    public function testPostUpdateWhenFilenameUnchanged(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->entityManager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn(['sampleField' => ['sampleValue1', 'sampleValue2']]);

        $this->fileManager->expects(self::never())
            ->method('deleteFile');

        $this->logger->expects(self::never())
            ->method('warning');

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }

    public function testPostUpdateWhenException(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->entityManager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn(['filename' => [$filename = 'name1', 'name2']]);

        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with($filename)
            ->willThrowException(new \Exception());

        $this->logger->expects(self::once())
            ->method('warning');

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }

    public function testPostUpdate(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->entityManager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn(['filename' => [$filename = 'name1', 'name2']]);

        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with($filename);

        $this->logger->expects(self::never())
            ->method('warning');

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }
}
