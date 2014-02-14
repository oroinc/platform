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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldMetadata;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var EntityData
     */
    protected $entityData;

    /**
     * @var array
     */
    protected $entities = array();

    /**
     * @var array
     */
    protected $entityFieldsMetadata = array();

    protected function setUp()
    {
        $this->entityMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldName = 'foo';

        $this->fieldMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->fieldMetadata->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue($this->fieldName));

        $this->entityMetadata
            ->expects($this->once())
            ->method('getFieldsMetadata')
            ->will($this->returnValue(array($this->fieldMetadata)));

        $this->entities[] = $this->createTestEntity(1);
        $this->entities[] = $this->createTestEntity(2);
        $this->entities[] = $this->createTestEntity(3);

        $entityFieldsMetadata = & $this->entityFieldsMetadata;
        $this->entityMetadata->expects($this->any())
            ->method('getFieldsMetadata')
            ->will(
                $this->returnCallback(
                    function () use (&$entityFieldsMetadata) {
                        return $entityFieldsMetadata;
                    }
                )
            );

        $this->entityData = new EntityData($this->entityMetadata, $this->entities);
    }

    public function testGetMetadata()
    {
        $this->assertEquals($this->entityMetadata, $this->entityData->getMetadata());
    }

    public function testGetEntities()
    {
        $this->assertEquals($this->entities, $this->entityData->getEntities());
    }

    public function testAddEntity()
    {
        $fooEntity = $this->createTestEntity(1);
        $barEntity = $this->createTestEntity(2);

        $expectedCount = count($this->entities) + 1;
        $expectedEntities = array_merge($this->entities, array($fooEntity));

        $this->assertEquals($this->entityData, $this->entityData->addEntity($fooEntity));

        $this->assertCount($expectedCount, $this->entityData->getEntities());
        $this->assertEquals($expectedEntities, $this->entityData->getEntities());

        $this->entityData->addEntity($barEntity);

        $expectedCount += 1;
        $expectedEntities = array_merge($expectedEntities, array($barEntity));

        $this->assertCount($expectedCount, $this->entityData->getEntities());
        $this->assertEquals($expectedEntities, $this->entityData->getEntities());
    }

    public function testGetEntityByOffset()
    {
        $fooEntity = $this->createTestEntity(1);
        $barEntity = $this->createTestEntity(2);

        $this->entityData->addEntity($fooEntity);
        $this->entityData->addEntity($barEntity);

        $this->assertEquals($fooEntity, $this->entityData->getEntityByOffset(0));
        $this->assertEquals($barEntity, $this->entityData->getEntityByOffset(1));
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\OutOfBoundsException
     * @expectedExceptionMessage "undefined" is illegal offset for getting entity.
     */
    public function testGetEntityByOffsetFails()
    {
        $this->entityData->getEntityByOffset('undefined');
    }

    public function testSetGetMasterEntity()
    {
        $this->assertEquals($this->entities[0], $this->entityData->getMasterEntity());

        $this->assertEquals($this->entityData, $this->entityData->setMasterEntity($this->entities[1]));
        $this->assertEquals($this->entities[1], $this->entityData->getMasterEntity());
    }

    public function testHasField()
    {
        $this->assertTrue($this->entityData->hasField($this->fieldName));
        $this->assertFalse($this->entityData->hasField('test'));
    }

    public function testGetField()
    {
        $field = $this->entityData->getField($this->fieldName);
        $this->assertInstanceOf('Oro\Bundle\EntityMergeBundle\Data\FieldData', $field);
        $this->assertEquals($this->fieldName, $field->getFieldName());
        $this->assertEquals($this->entityData, $field->getEntityData());
        $this->assertEquals($this->fieldMetadata, $field->getMetadata());
        $this->assertEquals($this->entities[0], $field->getSourceEntity());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Field "unknown" not exist.
     */
    public function testGetFieldFails()
    {
        $this->entityData->getField('unknown');
    }

    public function testGetFields()
    {
        $fields = $this->entityData->getFields();
        $this->assertCount(1, $fields);
        $this->assertInstanceOf('Oro\Bundle\EntityMergeBundle\Data\FieldData', $fields[$this->fieldName]);
    }

    protected function createTestEntity($id)
    {
        $result = new \stdClass();
        $result->id = $id;
        return $result;
    }
}
