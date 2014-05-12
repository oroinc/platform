<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Data;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;

class FieldDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityData;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldMetadata;

    /**
     * @var FieldData
     */
    protected $fieldData;

    protected function setUp()
    {
        $this->entityData = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->fieldData = new FieldData($this->entityData, $this->fieldMetadata);
    }

    public function testGetMetadata()
    {
        $this->assertEquals($this->fieldMetadata, $this->fieldData->getMetadata());
    }

    public function testSetGetSourceEntity()
    {
        $this->assertNull($this->fieldData->getSourceEntity());
        $entity = $this->createTestEntity(1);
        $this->assertEquals($this->fieldData, $this->fieldData->setSourceEntity($entity));
        $this->assertEquals($entity, $this->fieldData->getSourceEntity());
    }

    public function testSetGetMode()
    {
        $this->assertEquals(MergeModes::REPLACE, $this->fieldData->getMode());
        $this->assertEquals($this->fieldData, $this->fieldData->setMode(MergeModes::UNITE));
        $this->assertEquals(MergeModes::UNITE, $this->fieldData->getMode());
    }

    public function testGetEntityData()
    {
        $this->assertEquals($this->entityData, $this->fieldData->getEntityData());
    }

    public function testGetFieldName()
    {
        $fieldName = 'test';
        $this->fieldMetadata->expects($this->once())
            ->method('getFieldName')
            ->will($this->returnValue($fieldName));

        $this->assertEquals($fieldName, $this->fieldData->getFieldName());
    }

    protected function createTestEntity($id)
    {
        $result = new \stdClass();
        $result->id = $id;
        return $result;
    }
}
