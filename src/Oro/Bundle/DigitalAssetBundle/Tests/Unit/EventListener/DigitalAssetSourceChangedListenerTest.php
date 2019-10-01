<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Entity\Repository\DigitalAssetRepository;
use Oro\Bundle\DigitalAssetBundle\EventListener\DigitalAssetSourceChangedListener;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class DigitalAssetSourceChangedListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var PropertyAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyAccessor;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var DigitalAssetSourceChangedListener */
    private $listener;

    public function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->propertyAccessor = new PropertyAccessor();

        $this->listener = new DigitalAssetSourceChangedListener($this->propertyAccessor);
    }

    public function testPostUpdateNoDigitalAssetParent(): void
    {
        $entity = new File();

        $this->em->expects($this->never())->method('flush');
        $this->listener->postUpdate($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdateNoFilenameChanges(): void
    {
        $entity = new File();
        $entity->setParentEntityClass(DigitalAsset::class);

        $uow = $this->createMock(UnitOfWork::class);

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($entity))
            ->willReturn([]);

        $this->em->expects($this->never())->method('flush');
        $this->listener->postUpdate($entity, new LifecycleEventArgs($entity, $this->em));
    }

    public function testPostUpdate(): void
    {
        $sourceFile = (new File())
            ->setParentEntityClass(DigitalAsset::class)
            ->setParentEntityId($digitalAssetId = 1);

        $childFile1 = $this->getEntity(File::class, [
            'id' => 22,
            'filename' => 'old_image.jpg',
            'extension' => 'jpg'
        ]);

        $childFile2 = $this->getEntity(File::class, [
            'id' => 33,
            'filename' => 'old_image.jpg',
            'extension' => 'jpg'
        ]);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(DigitalAsset::class)
            ->willReturn($repo = $this->createMock(DigitalAssetRepository::class));

        $repo->expects($this->once())
            ->method('findChildFilesByDigitalAssetId')
            ->with($digitalAssetId)
            ->willReturn([$childFile1, $childFile2]);

        $uow = $this->createMock(UnitOfWork::class);

        $this->em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects($this->once())
            ->method('getEntityChangeSet')
            ->with($this->identicalTo($sourceFile))
            ->willReturn([
                'filename' => ['old_image.jpg', 'new_image.png'],
                'extension' => ['jpg', 'png'],
            ]);

        $this->em->expects($this->once())
            ->method('flush');

        $this->listener->postUpdate($sourceFile, new LifecycleEventArgs($sourceFile, $this->em));
        $this->assertSame('new_image.png', $childFile1->getFilename());
        $this->assertSame('png', $childFile1->getExtension());
        $this->assertSame('new_image.png', $childFile2->getFilename());
        $this->assertSame('png', $childFile2->getExtension());
    }
}
