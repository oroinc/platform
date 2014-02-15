<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Metadata;

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

    public function testHasDoctrineMetadata()
    {
        $metadata = new FieldMetadata($this->options);
        $this->assertFalse($metadata->hasDoctrineMetadata());

        $metadata->setDoctrineMetadata($this->doctrineMetadata);
        $this->assertTrue($metadata->hasDoctrineMetadata());
    }

    public function testGetFieldName()
    {
        $fieldName = 'field';

        $this->metadata->set('field_name', 'field');

        $this->assertEquals($fieldName, $this->metadata->getFieldName());
    }

    /**
     * @expectedException \Oro\Bundle\EntityMergeBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Cannot get field name from merge field metadata.
     */
    public function testGetFieldNameFails()
    {
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
