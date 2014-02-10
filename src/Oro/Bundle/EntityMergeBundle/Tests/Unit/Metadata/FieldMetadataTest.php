<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class FieldMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineMetadata;

    /**
     * @var FieldMetadata
     */
    protected $metadata;

    protected function setUp()
    {
        $this->options = array('foo' => 'bar');
        $this->doctrineMetadata = $this->createDoctrineMetadata();
        $this->metadata = new FieldMetadata($this->options, $this->doctrineMetadata);
    }

    public function testGetDoctrineMetadata()
    {
        $this->assertEquals($this->doctrineMetadata, $this->metadata->getDoctrineMetadata());
    }

    public function testGetFieldName()
    {
        $className = 'test';

        $this->doctrineMetadata->expects($this->once())
            ->method('has')
            ->with('fieldName')
            ->will($this->returnValue(true));

        $this->doctrineMetadata->expects($this->once())
            ->method('get')
            ->with('fieldName')
            ->will($this->returnValue($className));

        $this->assertEquals($className, $this->metadata->getFieldName());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Cannot get field name from merge field metadata.
     */
    public function testGetFieldNameFails()
    {
        $this->doctrineMetadata->expects($this->once())
            ->method('has')
            ->with('fieldName')
            ->will($this->returnValue(false));

        $this->metadata->getFieldName();
    }

    protected function createDoctrineMetadata()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata')
            ->disableOriginalConstructor()->getMock();
    }

    protected function createFieldMetadata()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->disableOriginalConstructor()->getMock();
    }
}
