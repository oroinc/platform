<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Data;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;

class EntityDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityMetadata;

    /**
     * @var EntityData
     */
    protected $entityData;

    protected function setUp()
    {
        $this->entityMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityData = new EntityData($this->entityMetadata);
    }

    public function testGetMetadata()
    {
        $this->assertEquals($this->entityMetadata, $this->entityData->getMetadata());
    }

    public function testSetEntities()
    {
        $entityClass = 'stdClass';

        $this->entityMetadata->expects($this->exactly(3))
            ->method('getClassName')
            ->will($this->returnValue($entityClass));

        $entities = array(
            $this->createTestEntity(1),
            $this->createTestEntity(2),
            $this->createTestEntity(3),
        );

        $this->assertEquals($this->entityData, $this->entityData->setEntities($entities));
        $this->assertEquals($entities, $this->entityData->getEntities());
    }

    public function testAddEntity()
    {
        $entityClass = 'stdClass';

        $this->entityMetadata->expects($this->exactly(2))
            ->method('getClassName')
            ->will($this->returnValue($entityClass));

        $fooEntity = $this->createTestEntity(1);
        $barEntity = $this->createTestEntity(2);

        $this->assertEquals($this->entityData, $this->entityData->addEntity($fooEntity));
        $this->assertCount(1, $this->entityData->getEntities());
        $this->assertEquals(array($fooEntity), $this->entityData->getEntities());

        $this->entityData->addEntity($fooEntity);
        $this->assertCount(1, $this->entityData->getEntities());

        $this->entityData->addEntity($barEntity);
        $this->assertCount(2, $this->entityData->getEntities());
        $this->assertEquals(array($fooEntity, $barEntity), $this->entityData->getEntities());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage $entity should be instance of SomeClass, stdClass given
     */
    public function testAddEntityFailsWhenMetadataClassNameNotMatch()
    {
        $entityClass = 'SomeClass';
        $this->entityMetadata->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue($entityClass));

        $entity = $this->createTestEntity(1);

        $this->entityData->addEntity($entity);
    }

    public function testHasEntity()
    {
        $entityClass = 'stdClass';

        $this->entityMetadata->expects($this->exactly(1))
            ->method('getClassName')
            ->will($this->returnValue($entityClass));

        $entity = $this->createTestEntity(1);

        $this->assertFalse($this->entityData->hasEntity($entity));

        $this->entityData->addEntity($entity);
        $this->assertTrue($this->entityData->hasEntity($entity));
    }

    protected function createTestEntity($id)
    {
        $result = new \stdClass();
        $result->id = $id;
        return $result;
    }
}
