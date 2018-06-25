<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\EventListener\FileListener;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\File\File as ComponentFile;

class FileListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FileListener  */
    protected $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject  */
    protected $fileManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject  */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject  */
    protected $em;

    /**
     * @var File
     */
    protected $attachment;

    public function setUp()
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->em = $this->createMock(EntityManager::class);

        $this->listener = new FileListener($this->fileManager, $this->tokenAccessor);
    }

    public function testPrePersistWithoutFileObject()
    {
        $entity = new File();

        $this->fileManager->expects($this->once())
            ->method('preUpload')
            ->with($entity);

        $this->listener->prePersist($entity, new LifecycleEventArgs($entity, $this->em));
        $this->assertNull($entity->getOwner());
    }

    public function testPrePersistWithFileObject()
    {
        $entity = new File();
        $file = new ComponentFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $entity->setFile($file);
        $loggedUser = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileManager->expects($this->once())
            ->method('preUpload')
            ->with($entity);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($loggedUser);

        $this->listener->prePersist($entity, new LifecycleEventArgs($entity, $this->em));
        $this->assertSame($loggedUser, $entity->getOwner());
    }

    public function testPreUpdateWithoutFileObject()
    {
        $entity = new File();

        $this->fileManager->expects($this->once())
            ->method('preUpload')
            ->with($entity);

        $this->listener->preUpdate($entity, new LifecycleEventArgs($entity, $this->em));
        $this->assertNull($entity->getOwner());
    }

    public function testPreUpdateWithFileObject()
    {
        $entity = new File();
        $file = new ComponentFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $entity->setFile($file);
        $loggedUser = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\User')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fileManager->expects($this->once())
            ->method('preUpload')
            ->with($entity);
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($loggedUser);

        $this->listener->preUpdate($entity, new LifecycleEventArgs($entity, $this->em));
        $this->assertSame($loggedUser, $entity->getOwner());
    }

    public function testPostPersistWhenFileObjectIsRemoved()
    {
        $entity = new File();
        $entity->setEmptyFile(true);

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($entity))
            ->willReturn(['filename' => ['test.txt', null]]);

        $this->fileManager->expects($this->once())
            ->method('upload')
            ->with($entity);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($entity);
        $this->fileManager->expects($this->once())
            ->method('deleteFile')
            ->with('test.txt');

        $this->listener->postPersist($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostPersistWithoutFileObject()
    {
        $entity = new File();

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($entity))
            ->willReturn(['filename' => ['test.txt', null]]);

        $this->fileManager->expects($this->once())
            ->method('upload')
            ->with($entity);
        $this->em->expects($this->never())
            ->method('remove');
        $this->fileManager->expects($this->once())
            ->method('deleteFile')
            ->with('test.txt');

        $this->listener->postPersist($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostPersistWithFileObject()
    {
        $entity = new File();
        $file = new ComponentFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $entity->setFile($file);

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($entity))
            ->willReturn(['filename' => ['test1.txt', 'test2.txt']]);

        $this->fileManager->expects($this->once())
            ->method('upload')
            ->with($entity);
        $this->em->expects($this->never())
            ->method('remove');
        $this->fileManager->expects($this->once())
            ->method('deleteFile')
            ->with('test1.txt');

        $this->listener->postPersist($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateWhenFileObjectIsRemoved()
    {
        $entity = new File();
        $entity->setEmptyFile(true);

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($entity))
            ->willReturn(['filename' => ['test.txt', null]]);

        $this->fileManager->expects($this->once())
            ->method('upload')
            ->with($entity);
        $this->em->expects($this->once())
            ->method('remove')
            ->with($entity);
        $this->fileManager->expects($this->once())
            ->method('deleteFile')
            ->with('test.txt');

        $this->listener->postUpdate($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateWithoutFileObject()
    {
        $entity = new File();

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($entity))
            ->willReturn(['filename' => ['test.txt', null]]);

        $this->fileManager->expects($this->once())
            ->method('upload')
            ->with($entity);
        $this->em->expects($this->never())
            ->method('remove');
        $this->fileManager->expects($this->once())
            ->method('deleteFile')
            ->with('test.txt');

        $this->listener->postUpdate($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateWithFileObject()
    {
        $entity = new File();
        $file = new ComponentFile(__DIR__ . '/../Fixtures/testFile/test.txt');
        $entity->setFile($file);

        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($entity))
            ->willReturn(['filename' => ['test1.txt', 'test2.txt']]);

        $this->fileManager->expects($this->once())
            ->method('upload')
            ->with($entity);
        $this->em->expects($this->never())
            ->method('remove');
        $this->fileManager->expects($this->once())
            ->method('deleteFile')
            ->with('test1.txt');

        $this->listener->postUpdate($entity, new LifecycleEventArgs($entity, $this->em));
    }
}
