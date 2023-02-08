<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\EventListener\FileDeleteListener as BaseFileDeleteListener;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\EventListener\FileDeleteListener;

class FileDeleteListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var BaseFileDeleteListener|\PHPUnit\Framework\MockObject\MockObject */
    private $innerFileDeleteListener;

    /** @var FileDeleteListener */
    private $listener;

    /** @var File|\PHPUnit\Framework\MockObject\MockObject */
    private $file;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    protected function setUp(): void
    {
        $this->innerFileDeleteListener = $this->createMock(BaseFileDeleteListener::class);

        $this->listener = new FileDeleteListener($this->innerFileDeleteListener);

        $this->entityManager = $this->createMock(EntityManager::class);
        $this->file = $this->getMockBuilder(File::class)
            ->addMethods(['getDigitalAsset'])
            ->getMock();
    }

    public function testPreRemove(): void
    {
        $this->file
            ->expects($this->once())
            ->method('getDigitalAsset');

        $this->listener->preRemove($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }

    public function testPostRemoveWhenDigitalAsset(): void
    {
        $this->file
            ->expects($this->once())
            ->method('getDigitalAsset')
            ->willReturn($this->createMock(DigitalAsset::class));

        $this->innerFileDeleteListener
            ->expects($this->never())
            ->method('postRemove');

        $this->listener->postRemove(
            $this->file,
            new LifecycleEventArgs($this->file, $this->entityManager)
        );
    }

    public function testPostRemove(): void
    {
        $this->innerFileDeleteListener
            ->expects($this->once())
            ->method('postRemove')
            ->with($this->file, $args = new LifecycleEventArgs($this->file, $this->entityManager));

        $this->listener->postRemove($this->file, $args);
    }

    public function testPostUpdateWhenNoDigitalAsset(): void
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

        $this->innerFileDeleteListener
            ->expects($this->once())
            ->method('postUpdate')
            ->with($this->file, $args = new LifecycleEventArgs($this->file, $this->entityManager));

        $this->listener->postUpdate($this->file, $args);
    }

    public function testPostUpdateWhenHasDigitalAsset(): void
    {
        $this->file
            ->expects($this->once())
            ->method('getDigitalAsset')
            ->willReturn($this->createMock(DigitalAsset::class));

        $this->entityManager
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork = $this->createMock(UnitOfWork::class));

        $unitOfWork
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn($changeSet = ['filename' => ['sampleOldValue', 'sampleNewValue']]);

        $this->innerFileDeleteListener
            ->expects($this->never())
            ->method('postUpdate');

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
            ->willReturn($changeSet = ['digitalAsset' => ['sampleOldValue', 'sampleNewValue']]);

        $this->innerFileDeleteListener
            ->expects($this->never())
            ->method('postUpdate');

        $this->listener->postUpdate($this->file, new LifecycleEventArgs($this->file, $this->entityManager));
    }
}
