<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\EventListener\MergeListener;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Bundle\TagBundle\Tests\Unit\Stub\NotTaggableEntityStub;
use Oro\Bundle\TagBundle\Tests\Unit\Stub\TaggableEntityStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MergeListenerTest extends TestCase
{
    private TagManager&MockObject $manager;
    private TaggableHelper&MockObject $helper;
    private EntityMetadata&MockObject $entityMetadata;
    private EntityData&MockObject $entityData;
    private MergeListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->manager = $this->createMock(TagManager::class);
        $this->helper = $this->createMock(TaggableHelper::class);
        $this->entityMetadata = $this->createMock(EntityMetadata::class);
        $this->entityData = $this->createMock(EntityData::class);

        $this->entityData->expects($this->any())
            ->method('getMetadata')
            ->willReturn($this->entityMetadata);

        $this->listener = new MergeListener($this->manager, $this->helper);
    }

    public function testOnBuildMetadata(): void
    {
        $this->helper->expects($this->once())
            ->method('isTaggable')
            ->willReturn(true);

        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn(TaggableEntityStub::class);
        $this->entityMetadata->expects($this->once())
            ->method('addFieldMetadata');

        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->listener->onBuildMetadata($event);
    }

    public function testOnCreateEntityData(): void
    {
        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn(TaggableEntityStub::class);

        $this->helper->expects($this->once())
            ->method('isTaggable')
            ->willReturn(true);

        $this->entityData->expects($this->any())
            ->method('getEntities')
            ->willReturn(new ArrayCollection([
                new TaggableEntityStub('foo'),
                new TaggableEntityStub('bar')
            ]));

        $this->manager->expects($this->exactly(2))
            ->method('loadTagging');

        $event = new EntityDataEvent($this->entityData);

        $this->listener->onCreateEntityData($event);
    }

    public function testAfterMergeEntity(): void
    {
        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn(TaggableEntityStub::class);

        $this->entityData->expects($this->any())
            ->method('getMasterEntity')
            ->willReturn(new TaggableEntityStub('foo'));

        $event = new EntityDataEvent($this->entityData);

        $this->manager->expects($this->once())
            ->method('saveTagging');

        $this->helper->expects($this->once())
            ->method('isTaggable')
            ->willReturn(true);

        $this->listener->afterMergeEntity($event);
    }

    public function testNotTaggable(): void
    {
        $this->helper->expects($this->once())
            ->method('isTaggable')
            ->willReturn(false);

        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->willReturn(NotTaggableEntityStub::class);
        $this->entityMetadata->expects($this->never())
            ->method('addFieldMetadata');

        $event = new EntityMetadataEvent($this->entityMetadata);

        $this->listener->onBuildMetadata($event);
    }
}
