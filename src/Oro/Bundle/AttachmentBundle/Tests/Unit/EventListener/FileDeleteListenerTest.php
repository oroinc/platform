<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\EventListener\FileDeleteListener;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Component\Testing\ReflectionUtil;
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

        $this->listener->postRemove($this->file);
        self::assertEquals([], ReflectionUtil::getPropertyValue($this->listener, 'filesShouldBeDeleted'));
    }

    public function testPostRemove(): void
    {
        $filename = 'sample/file';
        $this->file->setFilename($filename);

        $this->listener->postRemove($this->file);

        $filesShouldBeDeleted = ReflectionUtil::getPropertyValue($this->listener, 'filesShouldBeDeleted');
        self::assertEquals(['sample/file'], $filesShouldBeDeleted);
    }

    public function testPostUpdateWhenStoredExternally(): void
    {
        $this->file->setExternalUrl('http://example.org/image.png');

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
        self::assertEquals([], ReflectionUtil::getPropertyValue($this->listener, 'filesShouldBeDeleted'));
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

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));

        $filesShouldBeDeleted = ReflectionUtil::getPropertyValue($this->listener, 'filesShouldBeDeleted');
        self::assertEquals([], $filesShouldBeDeleted);
    }

    public function testPostUpdate(): void
    {
        $filename = 'name1';
        $unitOfWork = $this->createMock(UnitOfWork::class);
        $this->entityManager->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork);
        $unitOfWork->expects(self::once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn(['filename' => [$filename, 'name2']]);

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
        $filesShouldBeDeleted = ReflectionUtil::getPropertyValue($this->listener, 'filesShouldBeDeleted');
        self::assertEquals([$filename], $filesShouldBeDeleted);
    }

    public function testOnFlush(): void
    {
        ReflectionUtil::setPropertyValue($this->listener, 'filesShouldBeDeleted', ['test']);
        $this->listener->onFlush($this->createMock(OnFlushEventArgs::class));
        self::assertEquals([], ReflectionUtil::getPropertyValue($this->listener, 'filesShouldBeDeleted'));
    }

    public function testPostFlushWithEmptyFilesList(): void
    {
        $this->fileManager->expects(self::never())
            ->method('deleteFile');

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
    }

    public function testPostFlushWithExceptionDuringDeletion(): void
    {
        $exception = new \Exception('Test exception.');
        ReflectionUtil::setPropertyValue($this->listener, 'filesShouldBeDeleted', ['test']);
        $this->fileManager->expects(self::once())
            ->method('deleteFile')
            ->with('test')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Could not delete file "test"', ['exception' => $exception]);

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
        self::assertEquals([], ReflectionUtil::getPropertyValue($this->listener, 'filesShouldBeDeleted'));
    }

    public function testPostFlush(): void
    {
        ReflectionUtil::setPropertyValue($this->listener, 'filesShouldBeDeleted', ['test', 'test1']);
        $this->fileManager->expects(self::exactly(2))
            ->method('deleteFile')
            ->withConsecutive(['test'], ['test1']);

        $this->logger->expects(self::never())
            ->method('warning');

        $this->listener->postFlush($this->createMock(PostFlushEventArgs::class));
        self::assertEquals([], ReflectionUtil::getPropertyValue($this->listener, 'filesShouldBeDeleted'));
    }
}
