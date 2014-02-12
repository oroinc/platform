<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Data;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;

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

    /**
     * @var array
     */
    protected $entities = array();

    /**
     * @var int
     */
    protected $masterEntityId;

    /**
     * @var int
     */
    protected $secondEntityId;

    /**
     * @var int
     */
    protected $thirdEntityId;

    /**
     * @var \stdClass
     */
    protected $masterEntity;

    /**
     * @var string
     */
    protected $entityClass = 'stdClass';

    /**
     * @var array
     */
    protected $entityFieldsMetadata  = array();

    protected function setUp()
    {
        $this->entityMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->masterEntityId = rand();
        $this->secondEntityId = rand();
        $this->thirdEntityId = rand();
        $this->masterEntity = $this->createTestEntity($this->masterEntityId);
        $this->entities[] = $this->masterEntity;
        $this->entities[] = $this->createTestEntity($this->secondEntityId);
        $this->entities[] = $this->createTestEntity($this->thirdEntityId);

        $entityClass = & $this->entityClass;

        $this->entityMetadata->expects($this->any())
            ->method('getClassName')
            ->will(
                $this->returnCallback(
                    function () use (&$entityClass) {
                        return $entityClass;
                    }
                )
            );

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
        $this->resetEntityData();
    }

    public function testEntityMetadataShouldCheckClassNameExactlyOneTimeForOneEntity()
    {
        $entityClass = 'stdClass';
        $expected = count($this->entities);
        $this->entityMetadata->expects($this->exactly($expected))
            ->method('getClassName')
            ->will($this->returnValue($entityClass));
        $this->entityData = new EntityData($this->entityMetadata, $this->entities);
    }

    public function testGetMetadata()
    {
        $this->assertEquals($this->entityMetadata, $this->entityData->getMetadata());
    }

    public function testSetEntities()
    {
        $this->assertEquals($this->entities, $this->entityData->getEntities());
        $this->assertEquals($this->masterEntity, $this->entityData->getMasterEntity());
    }

    public function testAddEntity()
    {
        $fooEntity = $this->createTestEntity(1);
        $barEntity = $this->createTestEntity(2);

        $expectedSize = count($this->entities) + 1;
        $expectedArray = array_merge($this->entities, array($fooEntity));

        $this->assertEquals($this->entityData, $this->entityData->addEntity($fooEntity));
        $this->assertCount($expectedSize, $this->entityData->getEntities());
        $this->assertEquals($expectedArray, $this->entityData->getEntities());

        $this->entityData->addEntity($fooEntity);
        $this->assertCount($expectedSize, $this->entityData->getEntities());

        $this->entityData->addEntity($barEntity);

        $expectedSizeAfterAddDifferentEntity = $expectedSize + 1;
        $expectedArrayAfterAddDifferentEntity = array_merge($expectedArray, array($barEntity));

        $this->assertCount($expectedSizeAfterAddDifferentEntity, $this->entityData->getEntities());
        $this->assertEquals($expectedArrayAfterAddDifferentEntity, $this->entityData->getEntities());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage $entity should be instance of SomeClass, stdClass given
     */
    public function testAddEntityFailsWhenMetadataClassNameNotMatch()
    {
        $this->entityClass = 'SomeClass';

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

    public function testSetGetMasterEntity()
    {
        $entityClass = 'stdClass';

        $this->entityMetadata->expects($this->exactly(2))
            ->method('getClassName')
            ->will($this->returnValue($entityClass));

        $this->entityData->addEntity($fooEntity = $this->createTestEntity(1));
        $this->entityData->addEntity($barEntity = $this->createTestEntity(2));

        $this->assertEquals($this->entityData, $this->entityData->setMasterEntity($fooEntity));
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Add entity before setting it as master.
     */
    public function testSetMasterEntityFails()
    {
        $this->entityClass = 'stdClass';

        $this->entityData->addEntity($fooEntity = $this->createTestEntity(1));
        $barEntity = $this->createTestEntity(2);

        $this->assertEquals($this->entityData, $this->entityData->setMasterEntity($barEntity));
        $this->assertEquals($barEntity, $this->entityData->getMasterEntity());
    }

    protected function createTestEntity($id)
    {
        $result = new \stdClass();
        $result->id = $id;
        return $result;
    }

    public function testGetFields()
    {
        $this->assertEquals(array(), $this->entityData->getFields());

        $fieldName = 'test';
        $fieldMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldMetadata->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue($fieldName));

        $this->entityFieldsMetadata = array($fieldMetadata);
        $this->resetEntityData();
        $field = new FieldData($this->entityData, $fieldMetadata);
        $field->setSourceEntity($this->entityData->getMasterEntity());
        $this->assertEquals(array($fieldName => $field), $this->entityData->getFields());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Field "test" not exist.
     */
    public function testHasFieldReturnFalseIfFieldNotFoundAndExceptionWillThrow()
    {
        $fieldName = 'test';

        $this->entityData->getField($fieldName);
    }

    public function testHasFieldReturnTrueIfFieldNotFoundAndExceptionWillNotThrow()
    {
        $this->assertEquals(array(), $this->entityData->getFields());
        $fieldName = 'test';
        $fieldMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $fieldMetadata->expects($this->exactly(2))
            ->method('getFieldName')
            ->will($this->returnValue($fieldName));

        $this->entityFieldsMetadata = array($fieldMetadata);
        $this->resetEntityData();
        $field = $this->entityData->getField($fieldName);
        $this->assertTrue($field->getFieldName() == $fieldName);
    }

    protected function resetEntityData()
    {
        $this->entityData = new EntityData($this->entityMetadata, $this->entities);
    }
}
