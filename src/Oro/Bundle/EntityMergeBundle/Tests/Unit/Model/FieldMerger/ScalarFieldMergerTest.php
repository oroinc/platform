<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\FieldMerger;

use Oro\Bundle\EntityMergeBundle\Model\FieldMerger\ScalarFieldMerger;

class ScalarFieldMergerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScalarFieldMerger $merger ;
     */
    protected $merger;

    public function setUp()
    {
        $this->merger = new ScalarFieldMerger();
    }

    public function testSupports()
    {
        $fieldData        = $this->createFieldData();
        $fieldMetadata    = $this->createFieldMetadata();
        $doctrineMetadata = $this->createFieldMetadata();

        $doctrineMetadata
            ->expects($this->any())
            ->method('get')
            ->with('type')
            ->will($this->returnValue('string'));

        $fieldMetadata
            ->expects($this->once())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($doctrineMetadata));

        $fieldData
            ->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($fieldMetadata));

        $this->assertFalse($this->merger->supports($fieldData));
    }

    protected function createFieldData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->setMethods(['getMetadata'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createFieldMetadata()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->setMethods(['getMetadata', 'get', 'getDoctrineMetadata'])
            ->disableOriginalConstructor()
            ->getMock();
    }
}
