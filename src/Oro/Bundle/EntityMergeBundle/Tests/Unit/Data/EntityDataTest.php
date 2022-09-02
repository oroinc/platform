<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Data;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EntityMergeBundle\Exception\OutOfBoundsException;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class EntityDataTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $entityMetadata;

    /** @var FieldMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldMetadata;

    /** @var string */
    private $fieldName;

    /** @var EntityData */
    private $entityData;

    /** @var array */
    private $entities = [];

    /** @var array */
    private $entityFieldsMetadata = [];

    protected function setUp(): void
    {
        $this->entityMetadata = $this->createMock(EntityMetadata::class);

        $this->fieldName = 'foo';

        $this->entities[] = $this->createTestEntity(1);
        $this->entities[] = $this->createTestEntity(2);
        $this->entities[] = $this->createTestEntity(3);

        $this->fieldMetadata = $this->createMock(FieldMetadata::class);

        $this->fieldMetadata->expects($this->any())
            ->method('getFieldName')
            ->willReturn($this->fieldName);

        $this->entityMetadata->expects($this->once())
            ->method('getFieldsMetadata')
            ->willReturn([$this->fieldMetadata]);

        $entityFieldsMetadata = & $this->entityFieldsMetadata;
        $this->entityMetadata->expects($this->any())
            ->method('getFieldsMetadata')
            ->willReturnCallback(function () use (&$entityFieldsMetadata) {
                return $entityFieldsMetadata;
            });

        $this->entityData = new EntityData($this->entityMetadata, $this->entities);
        $this->entityData->setMasterEntity($this->entities[0]);
        $this->entityData->getField($this->fieldName)->setSourceEntity($this->entities[0]);
    }

    private function createTestEntity(int $id): \stdClass
    {
        $result = new \stdClass();
        $result->id = $id;

        return $result;
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
        $expectedEntities = array_merge($this->entities, [$fooEntity]);

        $this->assertEquals($this->entityData, $this->entityData->addEntity($fooEntity));

        $this->assertCount($expectedCount, $this->entityData->getEntities());
        $this->assertEquals($expectedEntities, $this->entityData->getEntities());

        $this->entityData->addEntity($barEntity);

        $expectedCount++;
        $expectedEntities = array_merge($expectedEntities, [$barEntity]);

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

    public function testGetEntityByOffsetFails()
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage('"undefined" is illegal offset for getting entity.');

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
        $this->assertInstanceOf(FieldData::class, $field);
        $this->assertEquals($this->fieldName, $field->getFieldName());
        $this->assertEquals($this->entityData, $field->getEntityData());
        $this->assertEquals($this->fieldMetadata, $field->getMetadata());
        $this->assertEquals($this->entities[0], $field->getSourceEntity());
    }

    public function testGetFieldFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field "unknown" not exist.');

        $this->entityData->getField('unknown');
    }

    public function testGetFields()
    {
        $fields = $this->entityData->getFields();
        $this->assertCount(1, $fields);
        $this->assertInstanceOf(FieldData::class, $fields[$this->fieldName]);
    }
}
