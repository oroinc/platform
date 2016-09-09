<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Oro\Bundle\EntityMergeBundle\Model\Accessor\DefaultAccessor;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\ReplaceStrategy;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class ReplaceStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ReplaceStrategy $strategy
     */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = new ReplaceStrategy(new DefaultAccessor());
    }

    public function testNotSupports()
    {
        $fieldData = $this->createFieldData();
        $fieldData
            ->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(MergeModes::UNITE));

        $this->assertFalse($this->strategy->supports($fieldData));
    }

    public function testSupports()
    {
        $fieldData = $this->createFieldData();
        $fieldData
            ->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(MergeModes::REPLACE));

        $this->assertTrue($this->strategy->supports($fieldData));
    }

    public function testMerge()
    {
        $fieldData         = $this->createFieldData();
        $fieldMetadataData = $this->createFieldMetadata();
        $entityData        = $this->createEntityData();
        $masterEntity      = new EntityStub(1);
        $sourceEntity      = new EntityStub(2);

        $fieldData
            ->expects($this->once())
            ->method('getEntityData')
            ->will($this->returnValue($entityData));

        $fieldMetadataData
            ->expects($this->any())
            ->method('has')
            ->will($this->returnValue(true));

        $fieldMetadataData
            ->expects($this->at(1))
            ->method('get')
            ->will($this->returnValue('getId'));

        $fieldMetadataData
            ->expects($this->at(3))
            ->method('get')
            ->will($this->returnValue('setId'));

        $fieldMetadataData
            ->expects($this->at(4))
            ->method('get')
            ->will($this->returnValue('setId'));

        $fieldData
            ->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($fieldMetadataData));

        $fieldData
            ->expects($this->once())
            ->method('getSourceEntity')
            ->will($this->returnValue($sourceEntity));

        $entityData
            ->expects($this->once())
            ->method('getMasterEntity')
            ->will($this->returnValue($masterEntity));

        $this->strategy->merge($fieldData);

        $this->assertEquals($sourceEntity->getId(), $masterEntity->getId());
    }

    public function testGetName()
    {
        $this->assertEquals('replace', $this->strategy->getName());
    }

    protected function createFieldData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->setMethods(['getMode', 'getEntityData', 'getMetadata', 'getSourceEntity'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createFieldMetadata()
    {
        return $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createEntityData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->setMethods(['getMasterEntity'])
            ->disableOriginalConstructor()
            ->getMock();
    }
}
