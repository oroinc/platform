<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityMergeBundle\Event\AfterMergeEvent;
use Oro\Bundle\EntityMergeBundle\Event\CreateEntityDataEvent;
use Oro\Bundle\EntityMergeBundle\Event\CreateMetadataEvent;
use Oro\Bundle\TagBundle\EventListener\MergeListener;
use Oro\Bundle\TagBundle\Tests\Unit\Stub\NotTaggableEntityStub;
use Oro\Bundle\TagBundle\Tests\Unit\Stub\TaggableEntityStub;

class MergeListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeListener
     */
    protected $listener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityData;

    protected function setUp()
    {
        $this->manager = $this->getMockBuilder('Oro\Bundle\TagBundle\Entity\TagManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new MergeListener($this->manager);

        $this->entityMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->setMethods(['getClassName', 'addFieldMetadata'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadata
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue(get_class($this->createTaggableEntity())));

        $this->entityData = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityData
            ->expects($this->any())
            ->method('getMetadata')
            ->will($this->returnValue($this->entityMetadata));
    }

    public function testOnCreateMetadata()
    {
        $this->entityMetadata
            ->expects($this->once())
            ->method('addFieldMetadata');

        $event = new CreateMetadataEvent($this->entityMetadata);

        $this->listener->onCreateMetadata($event);
    }

    public function testOnCreateEntityData()
    {
        $this->entityData
            ->expects($this->any())
            ->method('getEntities')
            ->will(
                $this->returnValue(
                    new ArrayCollection(
                        [
                            $this->createTaggableEntity('foo'),
                            $this->createTaggableEntity('bar')
                        ]
                    )
                )
            );

        $this->manager
            ->expects($this->exactly(2))
            ->method('loadTagging');

        $event = new CreateEntityDataEvent($this->entityData);

        $this->listener->onCreateEntityData($event);
    }

    public function testAfterMerge()
    {
        $this->entityData
            ->expects($this->any())
            ->method('getMasterEntity')
            ->will($this->returnValue($this->createTaggableEntity('foo')));

        $event = new AfterMergeEvent($this->entityData);

        $this->manager
            ->expects($this->once())
            ->method('saveTagging');

        $this->listener->afterMerge($event);
    }

    public function testNotTaggable()
    {
        $this->entityMetadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->setMethods(['getClassName', 'addFieldMetadata'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMetadata
            ->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue(get_class($this->createNotTaggableEntity())));

        $this->entityMetadata
            ->expects($this->never())
            ->method('addFieldMetadata');

        $event = new CreateMetadataEvent($this->entityMetadata);

        $this->listener->onCreateMetadata($event);
    }

    /**
     * @param mixed $id
     * @return TaggableEntityStub
     */
    protected function createTaggableEntity($id = null)
    {
        return new TaggableEntityStub($id);
    }

    /**
     * @param mixed $id
     * @return NotTaggableEntityStub
     */
    protected function createNotTaggableEntity($id = null)
    {
        return new NotTaggableEntityStub($id);
    }
}
