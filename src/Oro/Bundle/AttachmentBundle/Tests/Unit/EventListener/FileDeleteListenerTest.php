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
    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var File */
    private $file;

    /** @var FileDeleteListener */
    private $listener;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->file = new File();

        $this->listener = new FileDeleteListener($this->fileManager, $this->logger);
    }

    public function testPostRemoveWhenException(): void
    {
        $this->file->setFilename($filename = 'sample/file');

        $this->fileManager->expects($this->once())
            ->method('deleteFile')
            ->with($filename)
            ->willThrowException(new \Exception());

        $this->logger->expects($this->once())
            ->method('warning');

        $this->listener->postRemove($this->file);
    }

    public function testPostRemove(): void
    {
        $this->file->setFilename($filename = 'sample/file');

        $this->fileManager->expects($this->once())
            ->method('deleteFile')
            ->with($filename);

        $this->logger->expects($this->never())
            ->method('warning');

        $this->listener->postRemove($this->file);
    }

    public function testPostUpdateWhenFilenameUnchanged(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn(['sampleField' => ['sampleValue1', 'sampleValue2']]);

        $this->fileManager->expects($this->never())
            ->method('deleteFile');

        $this->logger->expects($this->never())
            ->method('warning');

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }

    public function testPostUpdateWhenException(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn(['filename' => [$filename = 'name1', 'name2']]);

        $this->fileManager->expects($this->once())
            ->method('deleteFile')
            ->with($filename)
            ->willThrowException(new \Exception());

        $this->logger->expects($this->once())
            ->method('warning');

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }

    public function testPostUpdate(): void
    {
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn(['filename' => [$filename = 'name1', 'name2']]);

        $this->fileManager->expects($this->once())
            ->method('deleteFile')
            ->with($filename);

        $this->logger->expects($this->never())
            ->method('warning');

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }
}
