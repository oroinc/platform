<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\EventListener\FileDigitalAssetChangedListener;
use Oro\Bundle\DigitalAssetBundle\Reflector\FileReflector;
use Oro\Bundle\DigitalAssetBundle\Tests\Unit\Stub\FileStub;
use Oro\Component\Testing\Unit\EntityTrait;

class FileDigitalAssetChangedListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FileReflector|\PHPUnit\Framework\MockObject\MockObject */
    private $fileReflector;

    /** @var FileDigitalAssetChangedListener */
    private $listener;

    /** @var LifecycleEventArgs|\PHPUnit\Framework\MockObject\MockObject */
    private $eventArgs;

    /** @var File|\PHPUnit\Framework\MockObject\MockObject */
    private $file;

    protected function setUp(): void
    {
        $this->fileReflector = $this->createMock(FileReflector::class);

        $this->listener = new FileDigitalAssetChangedListener($this->fileReflector);

        $this->eventArgs = $this->createMock(LifecycleEventArgs::class);
        $this->file = $this->getMockBuilder(File::class)
            ->addMethods(['getDigitalAsset'])
            ->getMock();
    }

    public function testPrePersistWhenNoDigitalAsset(): void
    {
        $this->fileReflector
            ->expects($this->never())
            ->method('reflectFromDigitalAsset');

        $this->listener->prePersist($this->file, $this->eventArgs);
    }

    public function testPrePersistWhenNewDigitalAsset(): void
    {
        $this->file
            ->expects($this->once())
            ->method('getDigitalAsset')
            ->willReturn($digitalAsset = $this->createMock(DigitalAsset::class));

        $this->fileReflector
            ->expects($this->never())
            ->method('reflectFromDigitalAsset');

        $this->listener->prePersist($this->file, $this->eventArgs);
    }

    public function testPrePersist(): void
    {
        $this->file
            ->expects($this->once())
            ->method('getDigitalAsset')
            ->willReturn($digitalAsset = $this->createMock(DigitalAsset::class));

        $digitalAsset
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->fileReflector
            ->expects($this->once())
            ->method('reflectFromDigitalAsset')
            ->with($this->file, $digitalAsset);

        $this->listener->prePersist($this->file, $this->eventArgs);
    }

    /**
     * @dataProvider preUpdateWhenDigitalAssetNotChangedDataProvider
     */
    public function testPreUpdateWhenDigitalAssetNotChanged(array $changeSet): void
    {
        $this->eventArgs
            ->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($entityManager = $this->createMock(EntityManager::class));

        $entityManager
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork = $this->createMock(UnitOfWork::class));

        $unitOfWork
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn($changeSet);

        $this->fileReflector
            ->expects($this->never())
            ->method('reflectFromDigitalAsset');

        $this->listener->preUpdate($this->file, $this->eventArgs);
    }

    public function preUpdateWhenDigitalAssetNotChangedDataProvider(): array
    {
        return [
            [
                'changeSet' => ['sampleField'],
            ],
            [
                'changeSet' => ['digitalAsset' => ['sampleOldValue', null]],
            ],
        ];
    }

    public function testPreUpdate(): void
    {
        $this->eventArgs
            ->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($entityManager = $this->createMock(EntityManager::class));

        $entityManager
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork = $this->createMock(UnitOfWork::class));

        $unitOfWork
            ->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->file)
            ->willReturn($changeSet = ['digitalAsset' => ['sampleOldValue', 'sampleNewValue']]);

        $this->file
            ->expects($this->once())
            ->method('getDigitalAsset')
            ->willReturn($digitalAsset = $this->createMock(DigitalAsset::class));

        $digitalAsset
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->fileReflector
            ->expects($this->once())
            ->method('reflectFromDigitalAsset')
            ->with($this->file, $digitalAsset);

        $this->listener->preUpdate($this->file, $this->eventArgs);
    }

    public function testFlush(): void
    {
        $onFlushEventArgs = $this->createMock(OnFlushEventArgs::class);
        $onFlushEventArgs
            ->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($entityManager = $this->createMock(EntityManager::class));

        $entityManager
            ->expects($this->once())
            ->method('getClassMetadata')
            ->with(File::class)
            ->willReturn($metadata = $this->createMock(ClassMetadata::class));

        $entityManager
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($unitOfWork = $this->createMock(UnitOfWork::class));

        /** @var DigitalAsset $digitalAssetWithId */
        $digitalAssetWithId = $this->getEntity(DigitalAsset::class, ['id' => 1]);
        $expectedDigitalAsset = new DigitalAsset();
        $expectedFile = (new FileStub())->setDigitalAsset($expectedDigitalAsset);
        $scheduledInsertions = [
            new \stdClass(),
            new FileStub(),
            (new FileStub())->setDigitalAsset($digitalAssetWithId),
            $expectedFile,
        ];

        $unitOfWork
            ->expects($this->once())
            ->method('getScheduledEntityInsertions')
            ->willReturn($scheduledInsertions);

        $this->fileReflector
            ->expects($this->once())
            ->method('reflectFromDigitalAsset')
            ->with($expectedFile, $expectedDigitalAsset);

        $unitOfWork
            ->expects($this->once())
            ->method('recomputeSingleEntityChangeSet')
            ->with($metadata, $expectedFile);

        $this->listener->onFlush($onFlushEventArgs);
    }
}
