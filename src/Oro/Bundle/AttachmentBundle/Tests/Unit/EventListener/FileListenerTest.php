<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Symfony\Component\HttpFoundation\File\File as ComponentFile;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\EventListener\FileListener;
use Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\TestClass;

class FileListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var FileListener  */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    protected $fileManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject  */
    protected $em;

    /**
     * @var File
     */
    protected $attachment;

    public function setUp()
    {
        $this->fileManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\FileManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacadeLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        $securityFacadeLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->securityFacade);

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new FileListener($this->fileManager, $securityFacadeLink);
    }

    public function testPrePersistForNotFileEntity()
    {
        $entity = new TestClass();

        $this->fileManager->expects($this->never())
            ->method('preUpload');

        $this->listener->prePersist(new LifecycleEventArgs($entity, $this->em));
    }

    public function testPrePersistForFileEntityButWithoutFileObject()
    {
        $entity = new File();

        $this->fileManager->expects($this->once())
            ->method('preUpload')
            ->with($entity);

        $this->listener->prePersist(new LifecycleEventArgs($entity, $this->em));
        $this->assertNull($entity->getOwner());
    }

    public function testPrePersistForFileEntityButWithFileObject()
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
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($loggedUser);

        $this->listener->prePersist(new LifecycleEventArgs($entity, $this->em));
        $this->assertSame($loggedUser, $entity->getOwner());
    }

    public function testPreUpdateForNotFileEntity()
    {
        $entity = new TestClass();

        $this->fileManager->expects($this->never())
            ->method('preUpload');

        $this->listener->preUpdate(new LifecycleEventArgs($entity, $this->em));
    }

    public function testPreUpdateForFileEntityButWithoutFileObject()
    {
        $entity = new File();

        $this->fileManager->expects($this->once())
            ->method('preUpload')
            ->with($entity);

        $this->listener->preUpdate(new LifecycleEventArgs($entity, $this->em));
        $this->assertNull($entity->getOwner());
    }

    public function testPreUpdateForFileEntityButWithFileObject()
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
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($loggedUser);

        $this->listener->preUpdate(new LifecycleEventArgs($entity, $this->em));
        $this->assertSame($loggedUser, $entity->getOwner());
    }

    public function testPostPersistForNotFileEntity()
    {
        $entity = new TestClass();

        $this->fileManager->expects($this->never())
            ->method('upload');

        $this->listener->postPersist(new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostPersistForFileEntityWhenFileObjectIsRemoved()
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

        $this->listener->postPersist(new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostPersistForFileEntityButWithoutFileObject()
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

        $this->listener->postPersist(new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostPersistForFileEntityButWithFileObject()
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

        $this->listener->postPersist(new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateForNotFileEntity()
    {
        $entity = new TestClass();

        $this->fileManager->expects($this->never())
            ->method('upload');

        $this->listener->postUpdate(new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateForFileEntityWhenFileObjectIsRemoved()
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

        $this->listener->postUpdate(new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateForFileEntityButWithoutFileObject()
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

        $this->listener->postUpdate(new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateForFileEntityButWithFileObject()
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

        $this->listener->postUpdate(new LifecycleEventArgs($entity, $this->em));
    }
}
