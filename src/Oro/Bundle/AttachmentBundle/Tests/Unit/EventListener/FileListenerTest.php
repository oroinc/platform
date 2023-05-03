<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\EventListener\FileListener;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FileListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var FileListener */
    private $listener;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->listener = new FileListener($this->fileManager, $this->tokenAccessor);
    }

    public function testPrePersistWhenManagedAndIsEmptyFile(): void
    {
        $entity = new File();
        $entity->setEmptyFile(true);

        $this->em->expects(self::once())
            ->method('contains')
            ->with($entity)
            ->willReturn(true);

        $this->em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork = $this->createMock(UnitOfWork::class));

        $unitOfWork->expects(self::once())
            ->method('clearEntityChangeSet')
            ->with(spl_object_hash($entity));

        $this->em->expects(self::once())
            ->method('refresh')
            ->with($entity);

        $this->fileManager->expects(self::never())
            ->method('preUpload');

        $this->listener->prePersist($entity, new LifecycleEventArgs($entity, $this->em));
        self::assertNull($entity->getOwner());
    }

    public function testPrePersistWithoutFileObject(): void
    {
        $entity = new File();

        $this->fileManager->expects(self::once())
            ->method('preUpload')
            ->with($entity);

        $this->tokenAccessor->expects(self::never())
            ->method('getUser');

        $this->listener->prePersist($entity, new LifecycleEventArgs($entity, $this->em));
        self::assertNull($entity->getOwner());
    }

    public function testPrePersistWithFileObject(): void
    {
        $entity = new File();
        $file = new ComponentFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $entity->setFile($file);
        $loggedUser = $this->createMock(User::class);

        $this->fileManager->expects(self::once())
            ->method('preUpload')
            ->with($entity);
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($loggedUser);

        $this->listener->prePersist($entity, new LifecycleEventArgs($entity, $this->em));
        self::assertSame($loggedUser, $entity->getOwner());
    }

    public function testPreUpdateWithoutFileObject(): void
    {
        $entity = new File();

        $this->fileManager->expects(self::once())
            ->method('preUpload')
            ->with($entity);

        $this->tokenAccessor->expects(self::never())
            ->method('getUser');

        $this->listener->preUpdate($entity, new LifecycleEventArgs($entity, $this->em));
        self::assertNull($entity->getOwner());
    }

    public function testPreUpdateWithFileObject(): void
    {
        $entity = new File();
        $file = new ComponentFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $entity->setFile($file);
        $loggedUser = $this->createMock(User::class);

        $this->fileManager->expects(self::once())
            ->method('preUpload')
            ->with($entity);
        $this->tokenAccessor->expects(self::once())
            ->method('getUser')
            ->willReturn($loggedUser);

        $this->listener->preUpdate($entity, new LifecycleEventArgs($entity, $this->em));
        self::assertSame($loggedUser, $entity->getOwner());
    }

    public function testPostPersistWhenFileObjectIsRemoved(): void
    {
        $entity = new File();
        $entity->setEmptyFile(true);

        $this->fileManager->expects(self::never())
            ->method('upload');
        $this->em->expects(self::once())
            ->method('remove')
            ->with($entity);

        $this->listener->postPersist($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostPersistWithoutFileObject(): void
    {
        $entity = new File();

        $this->fileManager->expects(self::once())
            ->method('upload')
            ->with($entity);
        $this->em->expects(self::never())
            ->method('remove');

        $this->listener->postPersist($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostPersistWithFileObject(): void
    {
        $entity = new File();
        $file = new ComponentFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $entity->setFile($file);

        $this->fileManager->expects(self::once())
            ->method('upload')
            ->with($entity);
        $this->em->expects(self::never())
            ->method('remove');

        $this->listener->postPersist($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateWhenFileObjectIsRemoved(): void
    {
        $entity = new File();
        $entity->setEmptyFile(true);

        $this->fileManager->expects(self::never())
            ->method('upload')
            ->with($entity);
        $this->em->expects(self::once())
            ->method('remove')
            ->with($entity);

        $this->listener->postUpdate($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateWithoutFileObject(): void
    {
        $entity = new File();

        $this->fileManager->expects(self::once())
            ->method('upload')
            ->with($entity);
        $this->em->expects(self::never())
            ->method('remove');

        $this->listener->postUpdate($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateWithFileObject(): void
    {
        $entity = new File();
        $file = new ComponentFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $entity->setFile($file);

        $this->fileManager->expects(self::once())
            ->method('upload')
            ->with($entity);
        $this->em->expects(self::never())
            ->method('remove');

        $this->listener->postUpdate($entity, new LifecycleEventArgs($entity, $this->em));
    }
}
