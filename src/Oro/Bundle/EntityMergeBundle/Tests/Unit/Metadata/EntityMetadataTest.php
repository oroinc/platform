<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

class EntityMetadataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var array
     */
    protected $fieldsMetadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineMetadata;

    /**
     * @var EntityMetadata
     */
    protected $metadata;

    protected function setUp()
    {
        $this->options = array('foo' => 'bar');
        $this->fieldsMetadata = array($this->createFieldMetadata());
        $this->doctrineMetadata = $this->createDoctrineMetadata();
        $this->metadata = new EntityMetadata($this->options, $this->fieldsMetadata, $this->doctrineMetadata);
    }

    public function testGetFieldsMetadata()
    {
        $this->assertEquals($this->fieldsMetadata, $this->metadata->getFieldsMetadata());
    }

    public function testGetDoctrineMetadata()
    {
        $this->assertEquals($this->doctrineMetadata, $this->metadata->getDoctrineMetadata());
    }

    public function testGetClassName()
    {
        $className = 'TestEntity';

        $this->doctrineMetadata->expects($this->once())
            ->method('has')
            ->with('name')
            ->will($this->returnValue(true));

        $this->doctrineMetadata->expects($this->once())
            ->method('get')
            ->with('name')
            ->will($this->returnValue($className));

        $this->assertEquals($className, $this->metadata->getClassName());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Cannot get class name from merge entity metadata.
     */
    public function testGetClassNameFails()
    {
        $this->doctrineMetadata->expects($this->once())
            ->method('has')
            ->with('name')
            ->will($this->returnValue(false));

        $this->metadata->getClassName();
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
