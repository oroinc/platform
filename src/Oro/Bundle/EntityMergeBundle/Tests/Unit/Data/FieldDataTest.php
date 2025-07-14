<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Data;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldDataTest extends TestCase
{
    private EntityData&MockObject $entityData;
    private FieldMetadata&MockObject $fieldMetadata;
    private FieldData $fieldData;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityData = $this->createMock(EntityData::class);
        $this->fieldMetadata = $this->createMock(FieldMetadata::class);

        $this->fieldData = new FieldData($this->entityData, $this->fieldMetadata);
    }

    private function createTestEntity(int $id): \stdClass
    {
        $result = new \stdClass();
        $result->id = $id;

        return $result;
    }

    public function testGetMetadata(): void
    {
        $this->assertEquals($this->fieldMetadata, $this->fieldData->getMetadata());
    }

    public function testSetGetSourceEntity(): void
    {
        $this->assertNull($this->fieldData->getSourceEntity());
        $entity = $this->createTestEntity(1);
        $this->assertEquals($this->fieldData, $this->fieldData->setSourceEntity($entity));
        $this->assertEquals($entity, $this->fieldData->getSourceEntity());
    }

    public function testSetGetMode(): void
    {
        $this->assertEquals(MergeModes::REPLACE, $this->fieldData->getMode());
        $this->assertEquals($this->fieldData, $this->fieldData->setMode(MergeModes::UNITE));
        $this->assertEquals(MergeModes::UNITE, $this->fieldData->getMode());
    }

    public function testGetEntityData(): void
    {
        $this->assertEquals($this->entityData, $this->fieldData->getEntityData());
    }

    public function testGetFieldName(): void
    {
        $fieldName = 'test';
        $this->fieldMetadata->expects($this->once())
            ->method('getFieldName')
            ->willReturn($fieldName);

        $this->assertEquals($fieldName, $this->fieldData->getFieldName());
    }
}
